# -*- coding: utf-8 -*-
"""Reads the 4 exam xlsx files and produces a clean, normalized init.sql.
Tables are built strictly from the task data (ООО «МирИгрушек»)."""
import openpyxl, io, os, datetime, re

BASE = r'C:\Users\Admin03\Downloads\Telegram Desktop\Прил_В4_КОД 09.02.07-2-2026-ПУ\Модуль 1\Прил_2_ОЗ_КОД 09.02.07-2-2026-М1\import'
OUT  = r'C:\Users\Admin03\Desktop\proekt\mirigrushek\init.sql'
APP_PASSWORD = '43215678$'   # all application users get this password (per user's instruction)


def rows(fname):
    wb = openpyxl.load_workbook(os.path.join(BASE, fname), data_only=True)
    ws = wb.worksheets[0]
    return [list(r) for r in ws.iter_rows(values_only=True)]


def q(v):
    """SQL-escape a value -> quoted string or NULL."""
    if v is None:
        return 'NULL'
    s = str(v).strip()
    if s == '':
        return 'NULL'
    return "'" + s.replace('\\', '\\\\').replace("'", "''") + "'"


def num(v):
    if v is None or str(v).strip() == '':
        return 'NULL'
    return str(v).strip()


def parse_date(v):
    """Return 'YYYY-MM-DD' or None (invalid/dirty dates -> None)."""
    if v is None:
        return None
    if isinstance(v, (datetime.datetime, datetime.date)):
        return v.strftime('%Y-%m-%d')
    s = str(v).strip()
    for fmt in ('%Y-%m-%d %H:%M:%S', '%Y-%m-%d', '%d.%m.%Y'):
        try:
            return datetime.datetime.strptime(s, fmt).strftime('%Y-%m-%d')
        except ValueError:
            pass
    return None  # e.g. "30.02.2025" – does not exist


def clean(rs):
    return [r for r in rs if any(c is not None and str(c).strip() != '' for c in r)]

# ---------- read source data ----------
tovar   = clean(rows('Tovar.xlsx')[1:])
users   = clean(rows('user_import.xlsx')[1:])
orders  = clean(rows('Заказ_import.xlsx')[1:])
points  = [r[0] for r in rows('Пункты выдачи_import.xlsx') if r and r[0]]

# distinct lookups (preserve first-seen order)
def distinct(seq):
    out = []
    for x in seq:
        x = (str(x).strip() if x is not None else '')
        if x and x not in out:
            out.append(x)
    return out

roles         = distinct(r[0] for r in users if r[0])
categories    = distinct(r[6] for r in tovar if r[6])
suppliers     = distinct(r[4] for r in tovar if r[4])
manufacturers = distinct(r[5] for r in tovar if r[5])
units         = distinct(r[2] for r in tovar if r[2])
statuses      = distinct(r[7] for r in orders if r[7])

idx = lambda lst: {v: i + 1 for i, v in enumerate(lst)}
ri, ci, si, mi, ui, sti = idx(roles), idx(categories), idx(suppliers), idx(manufacturers), idx(units), idx(statuses)

L = []
def w(s=''):
    s = str(s)
    s = '\n'.join(ln for ln in s.split('\n') if not ln.lstrip().startswith('--'))
    L.append(s)

w('-- ====================================================================')
w('--  ООО «МирИгрушек» — БД демонстрационного экзамена 09.02.07-2-2026')
w('--  Сгенерировано автоматически из import/*.xlsx. Кодировка: utf8mb4.')
w('-- ====================================================================')
w('SET NAMES utf8mb4;')
w('SET FOREIGN_KEY_CHECKS = 0;')
w('DROP DATABASE IF EXISTS mirigrushek;')
w('CREATE DATABASE mirigrushek CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;')
w('USE mirigrushek;')
w('')

# ---------- schema ----------
w('''-- ---------- Справочники ----------
CREATE TABLE Roles (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE Categories (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE Suppliers (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE Manufacturers (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE Units (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE OrderStatuses (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE PickupPoints (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  address VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- ---------- Пользователи ----------
CREATE TABLE Users (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  role_id   INT NOT NULL,
  full_name VARCHAR(200) NOT NULL,
  login     VARCHAR(150) NOT NULL UNIQUE,
  password  VARCHAR(150) NOT NULL,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES Roles(id)
) ENGINE=InnoDB;

-- ---------- Товары ----------
CREATE TABLE Products (
  article         VARCHAR(20) PRIMARY KEY,
  name            VARCHAR(500) NOT NULL,
  unit_id         INT NOT NULL,
  price           DECIMAL(10,2) NOT NULL CHECK (price >= 0),
  supplier_id     INT NOT NULL,
  manufacturer_id INT NOT NULL,
  category_id     INT NOT NULL,
  discount        INT NOT NULL DEFAULT 0 CHECK (discount >= 0),
  stock_qty       INT NOT NULL DEFAULT 0 CHECK (stock_qty >= 0),
  description     TEXT,
  photo           VARCHAR(255),
  CONSTRAINT fk_prod_unit FOREIGN KEY (unit_id)         REFERENCES Units(id),
  CONSTRAINT fk_prod_sup  FOREIGN KEY (supplier_id)     REFERENCES Suppliers(id),
  CONSTRAINT fk_prod_man  FOREIGN KEY (manufacturer_id) REFERENCES Manufacturers(id),
  CONSTRAINT fk_prod_cat  FOREIGN KEY (category_id)     REFERENCES Categories(id)
) ENGINE=InnoDB;

-- ---------- Заказы ----------
CREATE TABLE Orders (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  order_date     DATE,
  delivery_date  DATE,
  pickup_point_id INT,
  client_user_id INT,
  receive_code   VARCHAR(20),
  status_id      INT NOT NULL,
  CONSTRAINT fk_ord_point  FOREIGN KEY (pickup_point_id) REFERENCES PickupPoints(id),
  CONSTRAINT fk_ord_client FOREIGN KEY (client_user_id)  REFERENCES Users(id),
  CONSTRAINT fk_ord_status FOREIGN KEY (status_id)       REFERENCES OrderStatuses(id)
) ENGINE=InnoDB;

CREATE TABLE OrderItems (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  order_id        INT NOT NULL,
  product_article VARCHAR(20) NOT NULL,
  quantity        INT NOT NULL CHECK (quantity > 0),
  CONSTRAINT fk_oi_order   FOREIGN KEY (order_id)        REFERENCES Orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_oi_product FOREIGN KEY (product_article) REFERENCES Products(article)
) ENGINE=InnoDB;
''')

# ---------- reference data ----------
w('-- ---------- Данные справочников ----------')
for name, data in [('Roles', roles), ('Categories', categories), ('Suppliers', suppliers),
                   ('Manufacturers', manufacturers), ('Units', units), ('OrderStatuses', statuses)]:
    vals = ',\n  '.join('(%d, %s)' % (i + 1, q(v)) for i, v in enumerate(data))
    w('INSERT INTO %s (id, name) VALUES\n  %s;' % (name, vals))
    w('')

# pickup points
vals = ',\n  '.join('(%d, %s)' % (i + 1, q(p)) for i, p in enumerate(points))
w('INSERT INTO PickupPoints (id, address) VALUES\n  %s;' % vals)
w('')

# --- транслитерация фамилии -> логин (фамилия латиницей @mail.ru) ---
TR = {'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'e','ж':'zh','з':'z',
      'и':'i','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r',
      'с':'s','т':'t','у':'u','ф':'f','х':'kh','ц':'ts','ч':'ch','ш':'sh',
      'щ':'shch','ъ':'','ы':'y','ь':'','э':'e','ю':'yu','я':'ya'}

def translit(word):
    return ''.join(TR.get(ch, TR.get(ch.lower(), ch)) for ch in word.lower())

def make_login(full_name, seen):
    surname = str(full_name).strip().split()[0]      # фамилия = первое слово ФИО
    base = translit(surname)
    seen[base] = seen.get(base, 0) + 1
    suffix = '' if seen[base] == 1 else str(seen[base])  # совпадающие фамилии -> vorsin, vorsin2
    return base + suffix + '@mail.ru'

# users  (login = фамилия@mail.ru, password forced to APP_PASSWORD per instruction)
w('-- ---------- Пользователи (логин = фамилия латиницей @mail.ru, пароль = %s) ----------' % APP_PASSWORD)
ulines = []
user_id_by_name = {}
seen_login = {}
for i, r in enumerate(users):
    role, fio = r[0], r[1]
    uid = i + 1
    login = make_login(fio, seen_login)
    user_id_by_name.setdefault(str(fio).strip(), uid)  # first match for client lookup
    ulines.append('(%d, %d, %s, %s, %s)' % (uid, ri[str(role).strip()], q(fio), q(login), q(APP_PASSWORD)))
w('INSERT INTO Users (id, role_id, full_name, login, password) VALUES\n  ' + ',\n  '.join(ulines) + ';')
w('')

# products
w('-- ---------- Товары ----------')
plines = []
for r in tovar:
    art, name, unit, price, sup, man, cat, disc, qty, desc, photo = r[:11]
    plines.append('(%s, %s, %d, %s, %d, %d, %d, %s, %s, %s, %s)' % (
        q(art), q(name), ui[str(unit).strip()], num(price),
        si[str(sup).strip()], mi[str(man).strip()], ci[str(cat).strip()],
        num(disc), num(qty), q(desc), q(photo)))
w('INSERT INTO Products (article, name, unit_id, price, supplier_id, manufacturer_id, category_id, discount, stock_qty, description, photo) VALUES\n  '
  + ',\n  '.join(plines) + ';')
w('')

# orders + items
w('-- ---------- Заказы и состав заказов ----------')
olines, ilines = [], []
for r in orders:
    onum, items, odate, ddate, point, fio, code, status = r[:8]
    oid = int(onum)
    # client lookup: prefer an "Авторизированный клиент" with this name, else any
    fio_s = str(fio).strip()
    cid = None
    for i, u in enumerate(users):
        if str(u[1]).strip() == fio_s and 'клиент' in str(u[0]).lower():
            cid = i + 1; break
    if cid is None:
        cid = user_id_by_name.get(fio_s)
    od = parse_date(odate)
    dd = parse_date(ddate)
    pid = num(point)
    olines.append('(%d, %s, %s, %s, %s, %s, %d)' % (
        oid,
        ("'%s'" % od) if od else 'NULL',
        ("'%s'" % dd) if dd else 'NULL',
        pid if pid != 'NULL' else 'NULL',
        cid if cid else 'NULL',
        q(code), sti[str(status).strip()]))
    # parse "ART, qty, ART, qty"
    parts = [p.strip() for p in str(items).split(',') if p.strip() != '']
    for k in range(0, len(parts) - 1, 2):
        art = parts[k]
        try:
            qty = int(parts[k + 1])
        except ValueError:
            continue
        ilines.append("(%d, '%s', %d)" % (oid, art.replace("'", "''"), qty))

w('INSERT INTO Orders (id, order_date, delivery_date, pickup_point_id, client_user_id, receive_code, status_id) VALUES\n  '
  + ',\n  '.join(olines) + ';')
w('')
w('INSERT INTO OrderItems (order_id, product_article, quantity) VALUES\n  '
  + ',\n  '.join(ilines) + ';')
w('')
w('SET FOREIGN_KEY_CHECKS = 1;')
w('')

# ---------- root remote access + privileges ----------
w('''-- ====================================================================
--  Пользователь root: доступ отовсюду, все привилегии (для Workbench)
-- ====================================================================
ALTER USER 'root'@'localhost' IDENTIFIED WITH caching_sha2_password BY '43215678$';
CREATE USER IF NOT EXISTS 'root'@'%' IDENTIFIED WITH caching_sha2_password BY '43215678$';
ALTER USER 'root'@'%' IDENTIFIED WITH caching_sha2_password BY '43215678$';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;''')

with io.open(OUT, 'w', encoding='utf-8') as f:
    f.write('\n'.join(L))

print('Wrote', OUT)
print('roles=%d categories=%d suppliers=%d manufacturers=%d units=%d statuses=%d points=%d users=%d products=%d orders=%d items=%d'
      % (len(roles), len(categories), len(suppliers), len(manufacturers), len(units),
         len(statuses), len(points), len(users), len(tovar), len(orders), len(ilines)))
