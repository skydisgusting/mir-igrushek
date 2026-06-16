
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS mir_igrushek_bd;
CREATE DATABASE mir_igrushek_bd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mir_igrushek_bd;

CREATE TABLE roles (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE categories (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE suppliers (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE brands (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE units (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE statuses (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE pickup_pts (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  address VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE accounts (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  role_id   INT NOT NULL,
  full_name VARCHAR(200) NOT NULL,
  login     VARCHAR(150) NOT NULL UNIQUE,
  password  VARCHAR(150) NOT NULL,
  CONSTRAINT fk_acc_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE goods (
  sku         VARCHAR(20) PRIMARY KEY,
  name        VARCHAR(500) NOT NULL,
  unit_id     INT NOT NULL,
  price       DECIMAL(10,2) NOT NULL CHECK (price >= 0),
  supplier_id INT NOT NULL,
  brand_id    INT NOT NULL,
  category_id INT NOT NULL,
  discount    INT NOT NULL DEFAULT 0 CHECK (discount >= 0),
  stock       INT NOT NULL DEFAULT 0 CHECK (stock >= 0),
  descr       TEXT,
  photo       VARCHAR(255),
  CONSTRAINT fk_goods_unit  FOREIGN KEY (unit_id)      REFERENCES units(id),
  CONSTRAINT fk_goods_sup   FOREIGN KEY (supplier_id)  REFERENCES suppliers(id),
  CONSTRAINT fk_goods_brand FOREIGN KEY (brand_id)     REFERENCES brands(id),
  CONSTRAINT fk_goods_cat   FOREIGN KEY (category_id)  REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE orders (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  placed_on  DATE,
  deliver_on DATE,
  spot_id    INT,
  user_id    INT,
  claim_code VARCHAR(20),
  status_id  INT NOT NULL,
  CONSTRAINT fk_ord_spot   FOREIGN KEY (spot_id)   REFERENCES pickup_pts(id),
  CONSTRAINT fk_ord_user   FOREIGN KEY (user_id)   REFERENCES accounts(id),
  CONSTRAINT fk_ord_status FOREIGN KEY (status_id) REFERENCES statuses(id)
) ENGINE=InnoDB;

CREATE TABLE order_lines (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  sku      VARCHAR(20) NOT NULL,
  qty      INT NOT NULL CHECK (qty > 0),
  CONSTRAINT fk_ol_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_ol_good  FOREIGN KEY (sku)      REFERENCES goods(sku)
) ENGINE=InnoDB;


INSERT INTO roles (id, name) VALUES
  (1, 'Администратор'),
  (2, 'Менеджер'),
  (3, 'Авторизированный клиент');

INSERT INTO categories (id, name) VALUES
  (1, 'Игровой набор'),
  (2, 'Конструктор'),
  (3, 'Детский музыкальный инструмент'),
  (4, 'Машинка');

INSERT INTO suppliers (id, name) VALUES
  (1, 'Pikeshop'),
  (2, 'Playbig'),
  (3, 'Knauf'),
  (4, 'CHILITOY'),
  (5, 'Vinylon');

INSERT INTO brands (id, name) VALUES
  (1, 'ABSпластик'),
  (2, 'BambiniFelici'),
  (3, 'Junion');

INSERT INTO units (id, name) VALUES
  (1, 'шт.');

INSERT INTO statuses (id, name) VALUES
  (1, 'Завершен'),
  (2, 'Новый');

INSERT INTO pickup_pts (id, address) VALUES
  (1,  '420151, г. Лесной, ул. Вишневая, 32'),
  (2,  '125061, г. Лесной, ул. Подгорная, 8'),
  (3,  '630370, г. Лесной, ул. Шоссейная, 24'),
  (4,  '400562, г. Лесной, ул. Зеленая, 32'),
  (5,  '614510, г. Лесной, ул. Маяковского, 47'),
  (6,  '410542, г. Лесной, ул. Светлая, 46'),
  (7,  '620839, г. Лесной, ул. Цветочная, 8'),
  (8,  '443890, г. Лесной, ул. Коммунистическая, 1'),
  (9,  '603379, г. Лесной, ул. Спортивная, 46'),
  (10, '603721, г. Лесной, ул. Гоголя, 41'),
  (11, '410172, г. Лесной, ул. Северная, 13'),
  (12, '614611, г. Лесной, ул. Молодежная, 50'),
  (13, '454311, г.Лесной, ул. Новая, 19'),
  (14, '660007, г.Лесной, ул. Октябрьская, 19'),
  (15, '603036, г. Лесной, ул. Садовая, 4'),
  (16, '394060, г.Лесной, ул. Фрунзе, 43'),
  (17, '410661, г. Лесной, ул. Школьная, 50'),
  (18, '625590, г. Лесной, ул. Коммунистическая, 20'),
  (19, '625683, г. Лесной, ул. 8 Марта'),
  (20, '450983, г.Лесной, ул. Комсомольская, 26'),
  (21, '394782, г. Лесной, ул. Чехова, 3'),
  (22, '603002, г. Лесной, ул. Дзержинского, 28'),
  (23, '450558, г. Лесной, ул. Набережная, 30'),
  (24, '344288, г. Лесной, ул. Чехова, 1'),
  (25, '614164, г.Лесной,  ул. Степная, 30'),
  (26, '394242, г. Лесной, ул. Коммунистическая, 43'),
  (27, '660540, г. Лесной, ул. Солнечная, 25'),
  (28, '125837, г. Лесной, ул. Шоссейная, 40'),
  (29, '125703, г. Лесной, ул. Партизанская, 49'),
  (30, '625283, г. Лесной, ул. Победы, 46'),
  (31, '614753, г. Лесной, ул. Полевая, 35'),
  (32, '426030, г. Лесной, ул. Маяковского, 44'),
  (33, '450375, г. Лесной ул. Клубная, 44'),
  (34, '625560, г. Лесной, ул. Некрасова, 12'),
  (35, '630201, г. Лесной, ул. Комсомольская, 17'),
  (36, '190949, г. Лесной, ул. Мичурина, 26');


INSERT INTO accounts (id, role_id, full_name, login, password) VALUES
  (1,  1, 'Ворсин Петр Евгеньевич',        'vorsin@mail.ru',     'skzskz'),
  (2,  1, 'Старикова Елена Павловна',       'starikova@mail.ru',  'skzskz'),
  (3,  1, 'Одинцов Серафим Артёмович',      'odintsov@mail.ru',   'skzskz'),
  (4,  2, 'Михайлюк Анна Вячеславовна',     'mikhaylyuk@mail.ru', 'skzskz'),
  (5,  2, 'Ситдикова Елена Анатольевна',    'sitdikova@mail.ru',  'skzskz'),
  (6,  2, 'Никифорова Весения Николаевна',  'nikiforova@mail.ru', 'skzskz'),
  (7,  3, 'Степанов Михаил Артёмович',      'stepanov@mail.ru',   'skzskz'),
  (8,  3, 'Ворсин Петр Евгеньевич',         'vorsin2@mail.ru',    'skzskz'),
  (9,  3, 'Старикова Елена Павловна',        'starikova2@mail.ru', 'skzskz'),
  (10, 3, 'Сазонов Руслан Германович',       'sazonov@mail.ru',    'skzskz');


INSERT INTO goods (sku, name, unit_id, price, supplier_id, brand_id, category_id, discount, stock, descr, photo) VALUES
  ('PMEZMH', 'Детский игровой набор машинок Щенячий патруль / Dogs mini . 9 героев + 9 инерфионных машинок', 1, 1414, 1, 1, 1, 22, 50, 'Детский набор машинок с героями мультсериала «Щенячий патруль» подойдет как для мальчиков, так и для девочек. В детский набор входит 9 фигурок щенков спасателей.', '1.jpg'),
  ('BPV4MM', 'Конструктор Гарри Поттер Сова Букля 630 деталей совместим с lego harry potter, лего совместимый)', 1, 771, 2, 1, 2, 15, 26, 'Коллекционная модель Букля состоит из множества потрясающих элементов, а также специального механизма внутри. С его помощью можно плавно поднимать-опускать крылья птицы.', '2.jpg'),
  ('JVL42J', 'Музыкальные инструменты для детей, ксилофон, барабаны, развивающие игрушки, игрушки для детей', 1, 2750, 2, 2, 3, 15, 0,  'Откройте мир музыки для вашего ребенка с этой уникальной игрушкой! Это многофункциональное музыкальное чудо объединяет в себе всё, что нужно для творческого развития.', '3.jpg'),
  ('F895RB', 'Машинка игрушка диско шар светящаяся музыкальная', 1, 368, 3, 1, 4, 6, 7, 'Светящаяся музыкальная машина с диско шаром переливается разными цветами, играет ритмичные мелодии, объезжает препятствия и крутится.', '4.jpg'),
  ('3XBOTN', 'Игровой набор Hot Wheels Action Loop Cyclone Challenge Track, с машинкой и удобным хранением, HTK16', 1, 3426, 3, 2, 1, 10, 21, 'Игровой набор Hot Wheels Action Loop Cyclone Challenge Track — уникальная игра для испытания скорости и ловкости.', '5.jpg'),
  ('3L7RCZ', 'Игровой набор с деревянными машинками Стройплощадка Кран-Паркс, Junion', 1, 7400, 3, 3, 1, 15, 0,  'Игровой набор «Стройплощадка Кран-Паркс Junion» — большая игрушечная парковка с деревянными машинками и настоящим подъёмным краном.', '6.jpg'),
  ('S72AM3', 'Синтезатор детский с микрофоном 61 клавиша', 1, 1749, 4, 3, 3, 10, 35, 'Детский синтезатор с микрофоном для юных музыкантов. Помогает развивать творческий потенциал.', '7.jpg'),
  ('2G3280', 'Деревянный игровой набор JUNION Стройплощадка "Кран-Паркс" с подъёмным, строительным краном и машинками, 18 предметов, подвижные элементы', 1, 1624, 5, 3, 1, 9, 20, 'Игровой набор «Стройплощадка Кран-Паркс Junion» — большая игрушечная парковка с деревянными машинками и настоящим подъёмным краном.', '8.jpg'),
  ('MIO8YV', 'Музыкальная игрушка интерактивная Пульт, детский прорезыватель для малышей', 1, 305, 5, 2, 3, 9, 31, 'Музыкальная игрушка интерактивная Пульт, детский прорезыватель для малышей.', '9.jpg'),
  ('UER2QD', 'Большой набор опытов и экспериментов для детей 14 в 1', 1, 2506, 5, 2, 1, 8, 27, 'Большой набор опытов и экспериментов для детей 14 в 1.', '10.jpg');


INSERT INTO orders (id, placed_on, deliver_on, spot_id, user_id, claim_code, status_id) VALUES
  (1,  '2025-02-27', '2025-04-20', 1,  7,  '901', 1),
  (2,  '2024-09-28', '2025-04-21', 11, 8,  '902', 1),
  (3,  '2025-03-21', '2025-04-22', 2,  9,  '903', 1),
  (4,  '2025-02-20', '2025-04-23', 11, 10, '904', 1),
  (5,  '2025-03-17', '2025-04-24', 2,  7,  '905', 1),
  (6,  '2025-03-01', '2025-04-25', 15, 8,  '906', 1),
  (7,  NULL,         '2025-04-26', 3,  9,  '907', 1),
  (8,  '2025-03-31', '2025-04-27', 19, 10, '908', 2),
  (9,  '2025-04-02', '2025-04-28', 5,  9,  '909', 2),
  (10, '2025-04-03', '2025-04-29', 19, 10, '910', 2);

INSERT INTO order_lines (order_id, sku, qty) VALUES
  (1,  'PMEZMH', 2),
  (1,  'BPV4MM', 2),
  (2,  'JVL42J', 1),
  (2,  'F895RB', 1),
  (3,  '3XBOTN', 10),
  (3,  '3L7RCZ', 10),
  (4,  'S72AM3', 5),
  (4,  '2G3280', 4),
  (5,  'MIO8YV', 2),
  (5,  'UER2QD', 2),
  (6,  'PMEZMH', 2),
  (6,  'BPV4MM', 2),
  (7,  'JVL42J', 1),
  (7,  'F895RB', 1),
  (8,  '3XBOTN', 10),
  (8,  '3L7RCZ', 10),
  (9,  'S72AM3', 5),
  (9,  '2G3280', 4),
  (10, 'MIO8YV', 2),
  (10, 'UER2QD', 2);

SET FOREIGN_KEY_CHECKS = 1;

ALTER USER 'root'@'localhost' IDENTIFIED WITH caching_sha2_password BY 'skzskz';
CREATE USER IF NOT EXISTS 'root'@'%' IDENTIFIED WITH caching_sha2_password BY 'skzskz';
ALTER USER 'root'@'%' IDENTIFIED WITH caching_sha2_password BY 'skzskz';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;
