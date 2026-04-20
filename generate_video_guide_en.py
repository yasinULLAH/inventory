import os
import asyncio
import edge_tts
import subprocess

# Configuration
SCREENSHOT_DIR = "audit_assets/screenshots"
OUTPUT_DIR = "audit_assets/video_guide_en"
os.makedirs(OUTPUT_DIR, exist_ok=True)
os.makedirs(f"{OUTPUT_DIR}/audio", exist_ok=True)
os.makedirs(f"{OUTPUT_DIR}/temp_clips", exist_ok=True)

# Detailed English descriptions with a professional, human-like tone
DESCRIPTIONS_EN = {
    "dashboard.png": "Welcome to BNI Enterprises Dealer Management System. This is your primary dashboard. At a glance, you can monitor total stock levels, sales performance, and current profit margins. The interactive cards provide real-time updates on pending cheques and model-wise inventory distribution, ensuring you stay on top of your business operations.",
    "inventory.png": "The Inventory and Stock management section provides a comprehensive overview of your entire fleet. You can filter by status—such as 'In Stock' or 'Sold'—and search for specific chassis or motor numbers. Each entry displays detailed pricing, color, and current status, allowing for surgical precision in stock tracking.",
    "sale.png": "Recording a sale is streamlined and efficient. When you select a bike from your inventory, the system automatically pulls the purchase price and calculates the appropriate tax and profit margin. You can record customer details and payment methods, including cash, bank transfers, or cheques, all within a single interface.",
    "purchase.png": "The Purchase Entry module allows you to restock your inventory with ease. You can record bulk purchases from suppliers, documenting order dates, inventory arrival dates, and financial details such as cheque numbers and bank names. The system ensures that every bike added is tracked from the moment it enters your warehouse.",
    "returns.png": "Managing returns is simple with the Returns and Adjustment module. If a bike is returned, you can process the refund, record the return amount, and update the inventory status. The system maintains a full audit trail of the return, including the refund method used, ensuring financial transparency.",
    "cheques.png": "The Cheque Register is your central hub for financial management. It tracks all payments, receipts, and refunds made via cheque. You can easily mark cheques as cleared, bounced, or cancelled. The detailed summary at the top provides an immediate overview of your pending and cleared financial commitments.",
    "customer_ledger.png": "The Customer Ledger offers a deep dive into your client relationships. By selecting a customer, you can view their complete transaction history, including all debits, credits, and current balances. It also provides a dedicated purchase history, showing every bike they have ever bought from you.",
    "supplier_ledger.png": "Manage your supply chain effectively with the Supplier Ledger. This section documents every transaction with your suppliers, from purchase orders to payments. It helps you track exactly how much has been paid and what remains outstanding for every shipment received.",
    "reports.png": "The Reports suite provides powerful business intelligence. You can generate detailed reports on stock levels, tax payments, monthly sales summaries, and daily ledgers. These insights allow you to analyze trends, optimize your inventory, and make data-driven decisions for your business growth.",
    "models.png": "The Models section allows you to manage the specific bike categories and codes in your system. You can add new models, assign short codes, and view a summary of how many units of each model are currently in stock or have been sold. This is essential for maintaining an organized product catalog.",
    "customers.png": "Maintaining a clean customer database is vital. In this section, you can manage your list of registered buyers, including their contact information, CNIC numbers, and addresses. It serves as a central directory for all your client interactions and marketing efforts.",
    "suppliers.png": "The Suppliers module is where you manage your relationships with manufacturers and wholesalers. Store essential contact details and addresses for every vendor you work with, making it easy to reach out for new orders or support.",
    "settings.png": "The Settings panel gives you full control over the application's core parameters. Here, you can update your company name, branch details, and tax rates. You can also manage security by changing the admin password and ensure data safety with the built-in database backup and restore features.",
    "purchase_modal__.png": "When adding new stock, the purchase form allows for detailed data entry. You can input chassis and motor numbers for every unit, ensuring each bike is unique and traceable throughout its lifecycle in your dealership.",
    "sale_modal__.png": "The sales interface is designed for speed and accuracy. It automatically calculates margins and taxes based on your settings, allowing you to focus on the customer while the system handles the complex financial calculations."
}

async def generate_audio():
    print("Generating High-Quality English Male Voice (Andrew Neural)...")
    for img, text in DESCRIPTIONS_EN.items():
        audio_path = f"{OUTPUT_DIR}/audio/{img.replace('.png', '.mp3')}"
        if not os.path.exists(audio_path):
            communicate = edge_tts.Communicate(text, "en-US-AndrewNeural")
            await communicate.save(audio_path)
            print(f"Generated audio for {img}")

def create_clips():
    print("Creating video clips...")
    file_list = []
    for img, text in DESCRIPTIONS_EN.items():
        img_path = f"audit_assets/screenshots/{img}"
        audio_path = f"{OUTPUT_DIR}/audio/{img.replace('.png', '.mp3')}"
        clip_path = f"{OUTPUT_DIR}/temp_clips/{img.replace('.png', '.mp4')}"
        
        if os.path.exists(img_path) and os.path.exists(audio_path):
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
    final_output = "audit_assets/video_guide/BNI_Enterprises_Guide_English_Male.mp4"
    os.makedirs(os.path.dirname(final_output), exist_ok=True)
    cmd = [
        "ffmpeg", "-y",
        "-f", "concat", "-safe", "0", "-i", f"{OUTPUT_DIR}/concat_list.txt",
        "-c", "copy", final_output
    ]
    subprocess.run(cmd, capture_output=True)
    print(f"Final video generated: {final_output}")

async def main():
    await generate_audio()
    create_clips()
    concat_videos()

if __name__ == "__main__":
    asyncio.run(main())
