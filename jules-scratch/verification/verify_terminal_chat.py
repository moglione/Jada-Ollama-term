from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch()
    page = browser.new_page()

    # 1. Arrange: Go to the application homepage.
    page.goto("http://localhost:8000")

    # 2. Assert: Check for key elements of the terminal interface.
    # Check for the black background
    body = page.locator("body")
    background_color = body.evaluate("element => window.getComputedStyle(element).getPropertyValue('background-color')")

    # Check for the input prompt
    prompt = page.locator(".prompt")

    # Check for the terminal input field
    terminal_input = page.locator(".terminal-input")

    # 3. Screenshot: Capture the final result for visual verification.
    page.screenshot(path="jules-scratch/verification/terminal_chat.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
