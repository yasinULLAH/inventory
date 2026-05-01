# Comprehensive Application Guideline & Video Creation Instructions

Follow these instructions to create a **fully detailed**, professional, and high-quality guide for any future application. This manual ensures that not a single module, feature, or functionality is missed.

---

## 1. Research & Analysis Phase
Before writing a single word, you must exhaustively analyze the application's source code and existing documentation.

*   **Exhaustive Module Mapping:** List every single `?page=` or route in the application. Do not group them unless they are physically on the same screen.
*   **Database Inspection:** Look at the table schemas (SQL) to understand what data is being tracked (e.g., if there's a `loyalty_points` column in the `customers` table, the guide must explain how loyalty points are earned and redeemed).
*   **Functional Logic:** Trace the code to find "hidden" features (e.g., automatic stock deductions, batch number tracking, or secondary currency support).

## 2. Script Writing for Maximum Detail
The biggest mistake is brevity. A detailed guide requires a deep dive into **how** and **why** for every feature.

*   **Explain Every Field:** In the "Products" module, don't just say "add products." Explain what a Barcode vs. SKU is, how the "Cost Price" affects "Profit Reports," and what happens when the "Min Stock Level" is reached.
*   **Use "Human-Centric" Language:** Frame explanations around user benefits. Instead of "Logs login events," use "The Activity Log acts as your digital security guard, tracking every single login and administrative action to ensure your business data is always safe and auditable."
*   **Mandatory Inclusion:** Every single module identified in the Research Phase must have its own dedicated section in both the Master Guideline (.md) and the Video Script.

## 3. High-Quality Video Automation (Python + FFmpeg)
To create professional, smooth, and human-like video guidelines, use the following technical strategies:

### A. Professional Audio (Narration)
*   **Voice Selection:** Use `edge-tts` for high-quality, neural voices.
    *   **English:** Use `en-US-ChristopherNeural` or `en-US-AndrewNeural` for a deep, authoritative male tone.
    *   **Urdu:** Use `ur-PK-AsadNeural`.
*   **Smoothness & Pacing (SSML):** To make narration more human-like, use **SSML (Speech Synthesis Markup Language)**.
    *   Add `<break time="500ms"/>` between sentences.
    *   Adjust pitch and rate (e.g., slightly slower speed for complex technical explanations) to ensure clarity.
*   **Urdu Grammar Correction:** Always have the Urdu script reviewed for natural flow. Avoid literal translations; use industry-standard terms (e.g., use "رسید" instead of "بل" where appropriate, or "بقایا جات" for "outstanding balances").

### B. Visual Pacing
*   **Video Length:** If the explanation is long, the video clip must match it. FFmpeg's `-shortest` flag ensures the video stays on screen exactly as long as the narration lasts.
*   **Resolution:** Always scale to `1920:1080` and use `pad` filters to ensure screenshots are centered with clean black or branded backgrounds.

## 4. Final Validation Checklist
Before finalizing, verify the following:
1.  [ ] **Zero Omissions:** Have you included every page from the sidebar navigation?
2.  [ ] **Dual Themes:** Are both Light and Dark mode screenshots included in the `.md`?
3.  [ ] **Depth:** Is every button and form field on the page explained?
4.  [ ] **Voice Quality:** Does the Urdu narration sound like a professional presenter? (Use SSML if it sounds robotic).
5.  [ ] **Actionable:** Can a new user run the app perfectly after watching/reading this?

---

## 5. Sample Automation Script Structure
```python
# Use this template for the next app
DESCRIPTIONS = {
    "module_name.png": "A 3-4 sentence detailed explanation of the module, its purpose, and every key feature visible on the screen."
}
# ... script logic follows ...
```
