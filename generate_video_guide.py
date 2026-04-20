import os
from gtts import gTTS
import subprocess

# Configuration
SCREENSHOT_DIR = "audit_assets/screenshots"
OUTPUT_DIR = "audit_assets/video_guide"
os.makedirs(OUTPUT_DIR, exist_ok=True)
os.makedirs(f"{OUTPUT_DIR}/audio", exist_ok=True)
os.makedirs(f"{OUTPUT_DIR}/temp_clips", exist_ok=True)

# Urdu descriptions for each screenshot
DESCRIPTIONS = {
    "dashboard.png": "بی این آئی انٹرپرائزز میں خوش آمدید۔ یہ آپ کا ڈیش بورڈ ہے جہاں آپ انوینٹری، فروخت اور نفع کا مکمل خلاصہ دیکھ سکتے ہیں۔",
    "inventory.png": "انوینٹری سیکشن میں آپ تمام دستیاب بائیکس کی تفصیلات، چیسس نمبر اور قیمتِ خرید دیکھ سکتے ہیں۔",
    "sale.png": "سیلز سیکشن کے ذریعے آپ کسی بھی بائیک کی فروخت کا اندراج کر سکتے ہیں اور گاہک کی تفصیلات محفوظ کر سکتے ہیں۔",
    "purchase.png": "پرچیز سیکشن میں نئی بائیکس کے اسٹاک کا اندراج کیا جاتا ہے، جس میں سپلائر اور چیک کی تفصیلات بھی شامل ہوتی ہیں۔",
    "returns.png": "اگر کوئی بائیک واپس آتی ہے تو اس سیکشن کے ذریعے واپسی کا اندراج اور ریفنڈ مینیج کیا جاتا ہے۔",
    "cheques.png": "چیک رجسٹر میں تمام وصول شدہ اور جاری کردہ چیکس کا ریکارڈ رکھا جاتا ہے تاکہ ادائیگیوں کا پتہ چل سکے۔",
    "customer_ledger.png": "کسٹمر لیجر میں آپ ہر گاہک کے ساتھ ہونے والے لین دین اور بقایا جات کا مکمل حساب دیکھ سکتے ہیں۔",
    "supplier_ledger.png": "سپلائر لیجر میں سپلائرز کو دی جانے والی ادائیگیوں اور ان کے بقایا جات کی تفصیلات موجود ہوتی ہیں۔",
    "reports.png": "رپورٹس سیکشن آپ کو کاروبار کی مجموعی کارکردگی اور مالی حالات کے بارے میں تفصیلی معلومات فراہم کرتا ہے۔",
    "models.png": "ماڈلز سیکشن میں آپ بائیکس کے مختلف ماڈلز اور ان کے کوڈز کا اندراج اور انتظام کر سکتے ہیں۔",
    "customers.png": "یہاں آپ اپنے تمام رجسٹرڈ گاہکوں کی لسٹ اور ان کی بنیادی معلومات دیکھ سکتے ہیں۔",
    "suppliers.png": "سپلائرز سیکشن میں آپ ان تمام کمپنیز کی معلومات رکھ سکتے ہیں جن سے آپ مال خریدتے ہیں۔",
    "settings.php": "سیٹنگز میں آپ کمپنی کا نام، ٹیکس ریٹ، اور بیک اپ جیسے اہم فیچرز کو کنٹرول کر سکتے ہیں۔",
    "settings.png": "سیٹنگز میں آپ کمپنی کا نام، ٹیکس ریٹ، اور بیک اپ جیسے اہم فیچرز کو کنٹرول کر سکتے ہیں۔",
    "purchase_modal__.png": "نئی خریداری کے وقت آپ اس فارم کے ذریعے بائیک کے چیسس اور انجن نمبر کا اندراج کرتے ہیں۔",
    "sale_modal__.png": "فروخت کے وقت یہ فارم استعمال کیا جاتا ہے تاکہ قیمتِ فروخت اور گاہک کی معلومات درج کی جا سکیں۔"
}

def generate_audio():
    print("Generating Urdu audio files...")
    for img, text in DESCRIPTIONS.items():
        audio_path = f"{OUTPUT_DIR}/audio/{img.replace('.png', '.mp3')}"
        if not os.path.exists(audio_path):
            tts = gTTS(text=text, lang='ur')
            tts.save(audio_path)
            print(f"Generated audio for {img}")

def create_clips():
    print("Creating video clips...")
    file_list = []
    for img, text in DESCRIPTIONS.items():
        img_path = f"{SCREENSHOT_DIR}/{img}"
        audio_path = f"{OUTPUT_DIR}/audio/{img.replace('.png', '.mp3')}"
        clip_path = f"{OUTPUT_DIR}/temp_clips/{img.replace('.png', '.mp4')}"
        
        if os.path.exists(img_path) and os.path.exists(audio_path):
            # Command to create a video from image + audio
            # We scale the image to a standard 1080p for consistency
            cmd = [
                "ffmpeg", "-y",
                "-loop", "1", "-i", img_path,
                "-i", audio_path,
                "-c:v", "libx264", "-tune", "stillimage", "-c:a", "aac", "-b:a", "192k",
                "-pix_fmt", "yuv420p", "-shortest",
                "-vf", "scale=1920:1080:force_original_aspect_ratio=decrease,pad=1920:1080:(ow-iw)/2:(oh-ih)/2",
                clip_path
            ]
            subprocess.run(cmd, capture_output=True)
            file_list.append(f"file 'temp_clips/{img.replace('.png', '.mp4')}'")
            print(f"Created clip for {img}")
    
    with open(f"{OUTPUT_DIR}/concat_list.txt", "w") as f:
        f.write("\n".join(file_list))

def concat_videos():
    print("Concatenating clips into final video...")
    final_output = "audit_assets/video_guide/BNI_Enterprises_Guide_Urdu.mp4"
    cmd = [
        "ffmpeg", "-y",
        "-f", "concat", "-safe", "0", "-i", f"{OUTPUT_DIR}/concat_list.txt",
        "-c", "copy", final_output
    ]
    subprocess.run(cmd, capture_output=True)
    print(f"Final video generated: {final_output}")

if __name__ == "__main__":
    generate_audio()
    create_clips()
    concat_videos()
