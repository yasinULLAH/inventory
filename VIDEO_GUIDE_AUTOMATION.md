# Video Guide Automation Framework

This document provides a standardized workflow and a reusable script to generate high-quality video guidelines with AI voiceovers (English Male Neural & Urdu Female) for any application.

## 🚀 Workflow
1.  **Screenshots:** Ensure your app screenshots are saved in a specific folder (e.g., `audit_assets/screenshots/`).
2.  **Documentation:** Generate your standard `.md` documentation first to finalize the feature descriptions.
3.  **Video Generation:** Use the script below to convert those screenshots and descriptions into a synchronized video guide.

## 🛠 Prerequisites

Ensure you have the following installed on your system:
- **Python 3.x**
- **FFmpeg** (Must be in your System PATH)
- **Required Python Libraries:**
  ```powershell
  pip install gTTS edge-tts asyncio
  ```

## 📜 Automation Script Template (`generate_app_video.py`)

Save the following code as a Python file in your project root. You only need to update the `APP_NAME` and `DESCRIPTIONS` dictionary for each new app.

```python
import os
import asyncio
import edge_tts
from gtts import gTTS
import subprocess

# --- CONFIGURATION AREA ---
APP_NAME = "My_New_App"
SCREENSHOT_DIR = "audit_assets/screenshots"
OUTPUT_DIR = "audit_assets/video_guide"
VOICE_EN = "en-US-AndrewNeural" # High-quality Male Neural
LANG_UR = "ur"                  # Urdu Female (gTTS)

# Update this dictionary for your specific app
DESCRIPTIONS = {
    "dashboard.png": {
        "en": "This is the main dashboard where you can see all your key metrics at a glance.",
        "ur": "یہ مین ڈیش بورڈ ہے جہاں آپ تمام اہم معلومات ایک ساتھ دیکھ سکتے ہیں۔"
    },
    "settings.png": {
        "en": "The settings page allows you to customize the application behavior and manage security.",
        "ur": "سیٹنگز پیج کے ذریعے آپ ایپ کی ترتیبات اور سیکیورٹی کو مینیج کر سکتے ہیں۔"
    }
}
# --- END CONFIGURATION ---

async def generate_assets(lang):
    os.makedirs(f"{OUTPUT_DIR}/{lang}/audio", exist_ok=True)
    os.makedirs(f"{OUTPUT_DIR}/{lang}/temp_clips", exist_ok=True)
    
    file_list = []
    print(f"Generating {lang} version...")
    
    for img, texts in DESCRIPTIONS.items():
        img_path = f"{SCREENSHOT_DIR}/{img}"
        audio_path = f"{OUTPUT_DIR}/{lang}/audio/{img.replace('.png', '.mp3')}"
        clip_path = f"{OUTPUT_DIR}/{lang}/temp_clips/{img.replace('.png', '.mp4')}"
        
        if not os.path.exists(img_path): continue

        # Generate Audio
        if lang == "en":
            communicate = edge_tts.Communicate(texts['en'], VOICE_EN)
            await communicate.save(audio_path)
        else:
            tts = gTTS(text=texts['ur'], lang=LANG_UR)
            tts.save(audio_path)

        # Create Video Clip using FFmpeg
        cmd = [
            "ffmpeg", "-y", "-loop", "1", "-i", img_path, "-i", audio_path,
            "-c:v", "libx264", "-tune", "stillimage", "-c:a", "aac", "-b:a", "192k",
            "-pix_fmt", "yuv420p", "-shortest",
            "-vf", "scale=1920:1080:force_original_aspect_ratio=decrease,pad=1920:1080:(ow-iw)/2:(oh-ih)/2",
            clip_path
        ]
        subprocess.run(cmd, capture_output=True)
        file_list.append(f"file 'temp_clips/{img.replace('.png', '.mp4')}'")
    
    # Concatenate
    list_path = f"{OUTPUT_DIR}/{lang}/concat_list.txt"
    with open(list_path, "w") as f: f.write("\n".join(file_list))
    
    final_video = f"{OUTPUT_DIR}/{APP_NAME}_Guide_{lang}.mp4"
    concat_cmd = ["ffmpeg", "-y", "-f", "concat", "-safe", "0", "-i", list_path, "-c", "copy", final_video]
    subprocess.run(concat_cmd, capture_output=True)
    print(f"Success! Video created: {final_video}")

async def main():
    await generate_assets("en")
    await generate_assets("ur")

if __name__ == "__main__":
    asyncio.run(main())
```

## 💡 Pro Tips for Next Time
1.  **Consistency:** Keep your screenshot filenames identical to the keys in the `DESCRIPTIONS` dictionary.
2.  **Voice Options:** To see more human-like voices for English, run `edge-tts --list-voices`.
3.  **Scaling:** The script automatically scales screenshots to **1080p (1920x1080)**. If your screenshots are vertical (mobile), it will add black bars (padding) to keep them centered and professional.
4.  **Cleanup:** Once the final `.mp4` is generated, you can delete the `audio/` and `temp_clips/` folders to save space.
