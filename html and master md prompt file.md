# Master Documentation & UI Showcase Prompt

Use the prompt below to generate a world-class documentation set for any application. It is designed to ensure no technical detail is missed while creating a visually stunning, minimalist 3D interface.

---

## The Prompt

"Act as a Senior Lead Developer and Product Designer. I have a repository containing documentation (.md files), screenshots, and source code. Your task is to synthesize all available data into two definitive files: `master.md` and `master.html`.

### Phase 1: Exhaustive Research
1. Scan every `.md` file in the `docs` (or equivalent) directory.
2. Analyze the main source code (e.g., `index.php`, `main.py`, `app.js`) to identify hidden logic, database schemas, and security protocols not mentioned in the docs.
3. Map every screenshot in the `screenshots` folder to its specific functional module.

### Phase 2: The Definitive `master.md`
Create a GitHub-ready document that is the 'Single Source of Truth'. It MUST include:
- **Project DNA:** High-level overview, tech stack, and unique value propositions.
- **Deep-Dive Architecture:** Full database schema, ERD logic, and security/authentication flows.
- **Granular Module Breakdown:** For every module, list every form field, every filter, every action, and provide the relevant screenshot link.
- **Mathematical Core:** Explicitly document all business logic, tax formulas, or data processing algorithms.
- **Bilingual Manual:** A comprehensive user guide in both English and the primary local language (if applicable).

### Phase 3: The Minimalist 3D `master.html`
Create a high-end, minimalist product showcase using Vanilla CSS, GSAP, and Three.js.
- **Visual Identity:** Use a 'Clean-Room' minimalist aesthetic. Monochrome base (Black/White/Dark-Gray) with a single, sharp accent color (e.g., Electric Blue or Cyber Green). High-end typography (e.g., Inter, Space Grotesk).
- **3D Immersion:** 
    - Implement a Three.js background (e.g., floating geometry or a data-particle field) that is subtle and professional.
    - Add 'Spatial Depth' where sections feel like floating glass panels in a 3D environment.
    - Use CSS 3D transforms (`perspective` and `rotate`) to make content cards tilt and react to mouse movement.
- **Micro-Animations:** 
    - Magnetic buttons and interactive cursors.
    - GSAP ScrollTrigger for fluid, non-linear section transitions.
    - Screenshot 'Lightbox' or Z-axis expansion on hover.
- **Functional UX:** Include a persistent, minimalist sidebar for instant navigation across the massive amount of content.

**Mandate:** Do not summarize. Do not skip details. If a field exists in the code, it must exist in these documents. The goal is to create a 'Gold Master' documentation set that looks like a million-dollar product launch."
