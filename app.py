from flask import Flask, send_file

# Создаем экземпляр Flask приложения
app = Flask(__name__)

# Определяем маршрут для главной страницы
@app.route('/')
def serve_html():
    # Отправляем ваш HTML-файл при обращении к корню сайта
    return send_file('index.html')

# Vercel будет искать переменную с именем 'app'
# Это та самая точка входа, которую не мог найти Vercel
