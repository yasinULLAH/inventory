# Models Module

## Purpose
Manages the master catalog of bike and scooter models. Each model includes code, name, category, short code, and image. Shows inventory statistics and provides quick-action purchase/sell links.

## Form Fields & Controls
- **MODEL CODE**: [text] - Unique model identifier code.
- **MODEL NAME**: [text] - Display name of the model.
- **CATEGORY**: [text] - Category (e.g., Electric Scooter, Electric Bike).
- **SHORT CODE**: [text] - Abbreviated code for quick reference.
- **IMAGE**: [file] - Model image upload (auto-resize to 800px).

## Data Architecture (Tables)
| SR# | MODEL CODE | MODEL NAME | CATEGORY | SHORT CODE | IMAGE | TOTAL INVENTORY | IN STOCK | SOLD | ACTIONS |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | E8S M2 | E8S M2 Electric Scooter | Electric Scooter | E8S | 🖼 | 2 | 1 | 1 | ✏ Edit 🗑 🛒 📦 |
| 2 | E8S Pro | E8S Pro Electric Scooter | Electric Scooter | E8S Pro | 🖼 | 1 | 1 | 0 | ✏ Edit 🗑 🛒 📦 |
| 3 | LY SI | LY SI Electric Bike | Electric Bike | LY | 🖼 | 2 | 2 | 0 | ✏ Edit 🗑 🛒 📦 |

### Actions
- **✏ Edit**: Modify model details.
- **🗑 Delete**: Remove model (blocked if bikes linked).
- **📦 Purchase**: Quick-link to purchase entry with model pre-selected.
- **🛒 Sell**: Quick-link to sales with model filter.

## Visual Evidence
![Models Full Capture](../screenshots/models.png)
