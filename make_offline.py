import urllib.request

js_url = "https://cdn.jsdelivr.net/npm/chart.js"
js_file = "chart.js"

urllib.request.urlretrieve(js_url, js_file)

with open("index.php", "r", encoding="utf-8") as f:
    content = f.read()

content = content.replace("https://cdn.jsdelivr.net/npm/chart.js", "chart.js")

with open("index.php", "w", encoding="utf-8") as f:
    f.write(content)