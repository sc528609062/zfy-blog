import json
import os
from pathlib import Path
from playwright.sync_api import sync_playwright

root = Path(__file__).resolve().parent.parent
output = root / "storage/framework/testing/browser"
output.mkdir(parents=True, exist_ok=True)
base = os.environ.get("ZFY_TEST_URL", "http://127.0.0.1:8010")
results = []
with sync_playwright() as p:
    browser = p.chromium.launch(headless=True, executable_path="C:/Users/Admin/AppData/Local/Google/Chrome/Application/chrome.exe")
    context = browser.new_context(viewport={"width": 1440, "height": 1000})
    page = context.new_page()
    errors = []
    page.on("pageerror", lambda error: errors.append(str(error)))
    for width, label in [(1440, "desktop"), (390, "mobile")]:
        page.set_viewport_size({"width": width, "height": 1000 if width > 500 else 844})
        for index, path in enumerate(["/", "/posts", "/files", "/images", "/vip", "/shop", "/content/demo-content-1", "/login"]):
            response = page.goto(base + path, wait_until="networkidle")
            page.screenshot(path=str(output / (label + "-" + str(index) + ".png")), full_page=True)
            metrics = page.evaluate("""() => ({title: document.title, overflow: document.documentElement.scrollWidth > innerWidth + 2, brokenImages: [...document.images].filter(img => !img.complete || img.naturalWidth === 0).map(img => img.getAttribute('src'))})""")
            results.append({"viewport": label, "path": path, "status": response.status, **metrics})
    print(json.dumps({"pages": results, "errors": errors}, ensure_ascii=True, indent=2))
    (output / "results.json").write_text(json.dumps({"pages": results, "errors": errors}, ensure_ascii=False, indent=2), encoding="utf-8")
    browser.close()
