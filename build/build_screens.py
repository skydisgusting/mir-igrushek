# -*- coding: utf-8 -*-
import io, os, re, pathlib

ROOT = r'C:\Users\Admin03\Desktop\proekt\mirigrushek'
OUT  = os.path.join(ROOT, 'build', '_screens')
IMG  = os.path.join(ROOT, 'images')
os.makedirs(OUT, exist_ok=True)

common = io.open(os.path.join(ROOT, 'web', 'common.php'), encoding='utf-8').read()
css = re.search(r'<style>(.*?)</style>', common, re.S).group(1)

def uri(name):
    return pathlib.Path(os.path.join(IMG, name)).as_uri()

LOGO = uri('icon.png')

def page(title, who, nav, body):
    return f'''<!doctype html><html lang="ru"><head><meta charset="utf-8">
<style>{css}</style></head><body>
<header class="top"><img class="logo" src="{LOGO}" alt="logo"><h1>{title}</h1>
<div class="who">{who}</div></header>
<nav>{nav}</nav><main>{body}</main></body></html>'''

def card(name, cat, desc, sup, man, art, stock, unit, discount, price):
    final = round(price * (1 - discount/100), 2)
    state = 'oos' if stock == 0 else ('sale' if discount > 17 else '')
    fmt = lambda v: f'{v:,.2f}'.replace(',', ' ').replace('.', ',')
    if discount > 0:
        priceblk = f'<span class="old">{fmt(price)} ₽</span><span class="new">{fmt(final)} ₽</span>'
    else:
        priceblk = f'<span class="new">{fmt(price)} ₽</span>'
    return f'''<div class="card {state}">
<img class="photo" src="{uri(art)}" alt="">
<h3>{name}</h3>
<div class="meta"><span class="badge">{cat}</span><br>{desc}<br>
<small>Поставщик: {sup} · Производитель: {man}</small><br>
<small>На складе: {stock} {unit} · Скидка: {discount}%</small></div>
<div class="price">{priceblk}</div></div>'''

nav_mgr = ('<a class="btn" href="#">Товары</a><a class="btn" href="#">Заказы</a>'
           '<span style="flex:1"></span><a class="btn" href="#">Выйти</a>')

# ---- Каталог ----
filt = ('<form class="bar"><label>Поиск по наименованию<input type="text" placeholder="введите название…"></label>'
        '<label>Категория<select><option>— все —</option></select></label>'
        '<label>Сортировка<select><option>— без сортировки —</option></select></label>'
        '<button class="btn accent" type="button">Применить</button>'
        '<a class="btn" href="#">Сбросить</a></form>')
cards = (
    card('Набор машинок «Щенячий патруль»', 'Игровой набор',
         'Детский набор машинок с героями мультсериала.', 'Pikeshop', 'ABSпластик', '1.jpg', 50, 'шт.', 22, 1414) +
    card('Синтезатор детский с микрофоном', 'Детский музыкальный инструмент',
         'Компактный синтезатор с микрофоном для юных музыкантов.', 'CHILITOY', 'Junion', '7.jpg', 35, 'шт.', 10, 1749) +
    card('Музыкальные инструменты для детей', 'Детский музыкальный инструмент',
         'Ксилофон, барабаны, развивающие игрушки.', 'Playbig', 'BambiniFelici', '3.jpg', 0, 'шт.', 15, 2750)
)
io.open(os.path.join(OUT, 'catalog.html'), 'w', encoding='utf-8').write(
    page('Каталог товаров', 'Михайлюк Анна Вячеславовна · <b>Менеджер</b>', nav_mgr,
         filt + '<div class="grid">' + cards + '</div>'))

# ---- Окно входа ----
login_body = '''<div style="max-width:420px;margin:0 auto;">
<form style="display:flex;flex-direction:column;gap:14px;">
<label>Логин (e-mail)<input type="text" value="vorsin@mail.ru"></label>
<label>Пароль<input type="password" value="123456"></label>
<button class="btn accent" type="button">Войти</button>
<a class="btn" href="#">Регистрация нового пользователя</a>
<a class="btn" href="#">Войти как гость (только просмотр товаров)</a>
</form></div>'''
io.open(os.path.join(OUT, 'login.html'), 'w', encoding='utf-8').write(
    page('Вход в систему', 'Гость',
         '<a class="btn" href="#">Товары</a><span style="flex:1"></span><a class="btn accent" href="#">Войти</a>',
         login_body))

# ---- Заказы ----
rows = [
    ('1', 'PMEZMH ×2, BPV4MM ×2', 'Завершен', '420151, г. Лесной, ул. Вишневая, 32', '2025-02-27', '2025-04-20', '901'),
    ('8', '3XBOTN ×10, 3L7RCZ ×10', 'Новый', '454311, г.Лесной, ул. Новая, 19', '2025-03-31', '2025-04-27', '908'),
    ('9', 'S72AM3 ×5, 2G3280 ×4', 'Новый', '614510, г. Лесной, ул. Маяковского, 47', '2025-04-02', '2025-04-28', '909'),
]
trs = ''.join('<tr>' + ''.join(f'<td>{c}</td>' for c in r) + '<td><a class="btn" href="#">Изменить</a></td></tr>' for r in rows)
orders_body = ('<p><a class="btn accent" href="#">+ Новый заказ</a></p>'
               '<table><tr><th>№</th><th>Артикулы заказа</th><th>Статус</th><th>Адрес пункта выдачи</th>'
               '<th>Дата заказа</th><th>Дата доставки</th><th>Код</th><th>Действия</th></tr>' + trs + '</table>')
io.open(os.path.join(OUT, 'orders.html'), 'w', encoding='utf-8').write(
    page('Заказы', 'Одинцов Серафим Артёмович · <b>Администратор</b>', nav_mgr, orders_body))

print('html written to', OUT)
