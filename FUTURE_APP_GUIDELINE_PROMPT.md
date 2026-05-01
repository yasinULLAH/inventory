# MASTER PROMPT: Application Video Guideline Generation

**Copy and paste the text below into a new session when you want to generate a guideline for a new app.**

---

## 1. Context & Assets
I have an application in this folder. Here are the assets available:
- **Main App File:** `[INSERT_MAIN_FILE_NAME.php/py/js]`
- **Screenshots:** Located in `[INSERT_FOLDER_NAME]`. There are Light and Dark mode variants for every page.
- **Documentation:** Relevant `.md` files for each module are in `[INSERT_FOLDER_NAME]`.

## 2. Your Task (Step-by-Step)
You are a Senior Technical Content Creator. I want you to generate a **Master Guideline (.md)** and then **Two Video Guidelines (English Male & Urdu Male voices)** using Python and FFmpeg.

### Step 1: Deep Research (Mandatory)
1. **Exhaustive Mapping:** Read the main app file and the sidebar/navigation logic. Map every single module and sub-module. **STRICT RULE: Do not miss a single page or feature.**
2. **Feature Analysis:** Look at the database schema and form fields. Understand **how** each feature works (e.g., how stock is deducted, how taxes are calculated).
3. **Screenshot Review:** Read all screenshots and their corresponding `.md` info files to align the visual state with the technical functionality.

### Step 2: Create Master Master.md
Generate a `master.md` file. For **EVERY** module:
- Provide a **Detailed English** section (3-4 paragraphs) explaining the purpose, every field, and every button.
- Provide a **Detailed Urdu** section using professional, human-like, and smooth terminology (e.g., use "بقایا جات" for outstanding, "رسید" for receipts).
- Embed the relevant Dark and Light screenshots.

### Step 3: Video Automation (Python + FFmpeg)
Create a Python script that:
1. **Narration Scripts:** Contains a dictionary of **long and detailed** narrations for each module.
2. **High-Quality TTS:** Uses `edge-tts` with `en-US-ChristopherNeural` (English) and `ur-PK-AsadNeural` (Urdu).
3. **Human-Like Pacing:** Use SSML tags (like `<break time="500ms"/>`) to ensure the narration is smooth, professional, and not robotic.
4. **Length Match:** The video must stay on the screen for the **full duration** of the narration. Do not make the videos too short.
5. **Final Output:** Generates `[APP_NAME]_Guide_EN.mp4` and `[APP_NAME]_Guide_UR.mp4`.

## 3. Strict Quality Standards
- **Zero Omissions:** Every single module in the app must be explained.
- **Technical Depth:** Don't just list features; explain the business logic behind them.
- **Professional Tone:** The Urdu narration must sound like a human presenter, not a machine translation.
- **Resolution:** All clips must be 1080p (1920x1080).

---
**Plan your research first, then execute.**
