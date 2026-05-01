# UNIVERSAL PROMPT: APP-TO-VIDEO GUIDELINE GENERATOR

**Instruction for the AI:** Use this prompt to transform a source codebase and its visual assets into a professional, human-like video guideline.

---

## 1. Input Data Structure
The user will provide the following:
- **Main Source File:** (e.g., `index.php`, `main.py`, `App.tsx`) representing the core logic and navigation of the application.
- **Assets Folder:** A directory containing screenshots of every page/module.
- **Metadata Folder:** A directory containing `.md` files for each screenshot/module describing its functional purpose and fields.

## 2. Your Mission (Role: Senior Multimedia Engineer)
Your goal is to autonomously research, plan, and execute a complete documentation and video suite. Follow these phases strictly:

### Phase 1: Exhaustive Architectural Mapping
- **Navigation Discovery:** Analyze the main source file's navigation logic (sidebars, menus, routes). List **every single page** and functional module.
- **Feature Deep-Dive:** Cross-reference the source code with the provided `.md` metadata and screenshots. Identify every form field, button action, and "hidden" business logic (e.g., database updates, automated calculations).
- **STRICT REQUIREMENT:** Do not group or skip modules. If a page exists in the app, it must be in the guideline.

### Phase 2: Master Documentation (Master.md)
Generate a comprehensive `master.md` file. For **EVERY** module identified:
- **Technical English Section:** Write detailed paragraphs. Explain the "Why" (Business Case) and the "How" (Functional Workflow). Detail every field label and button.
- **Human-Like Urdu Section:** Provide a smooth, professional translation. Use industry-standard Urdu terminology (e.g., "نفع و نقصان" for Profit/Loss, "بقایا جات" for Balances). Avoid robotic literal translations.
- **Visual Embedding:** Embed the relevant Light and Dark mode screenshots directly into the section.

### Phase 3: Professional Video Automation (Python + FFmpeg)
Generate a robust Python script to automate video production:
- **Narrative Scripting:** Create a dictionary with **long, detailed, and professional narrations** for each module. The narration should sound like a human expert explaining the app to a client.
- **High-Quality TTS:** Use `edge-tts` with:
    - **English:** `en-US-ChristopherNeural` (Professional Male).
    - **Urdu:** `ur-PK-AsadNeural` (Professional Male).
- **SSML Pacing:** Integrate SSML tags (e.g., `<break time="700ms"/>`, `<prosody rate="-10%">`) to ensure the speech is natural, has proper pauses, and is easy to follow.
- **Clip Syncing:** Use `ffmpeg` to ensure the screenshot remains on screen for the **full duration** of the narration.
- **Formatting:** Ensure all outputs are 1080p (1920x1080) with screenshots scaled and padded correctly.

## 3. Strict Quality Mandates
- **Detail over Brevity:** The client wants *more* information, not less. Explain the logic, not just the labels.
- **Zero Omissions:** Missing a single feature or page is considered a failure.
- **Human Connection:** The tone must be helpful, professional, and engaging—exactly like a senior consultant presenting the app to a CEO.

---
**Plan your research and script narrations first, then proceed to implementation.**
