@index.php

# OMNI Audit Lessons Learned & Technical Guidelines

## 1. The Two-Step Execution Protocol
To ensure maximum stability and allow for human review, the audit must always be performed in two distinct phases:
- **Phase 1: Discovery & Asset Capture**: Spider the application, capture high-DPI full-page screenshots, and generate comprehensive GitHub-style `.md` files for every module.
- **Phase 2: Corporate Reconstruction**: Generate the final `.docx` manual using only the local assets captured in Phase 1. This prevents browser/session timeouts from affecting document generation.

## 2. Technical Standards (Node.js 24+ Compatibility)
- **Image Handling**: `docx` v9.x in Node 24+ environment has strict requirements for binary data. 
    - **Mistake to Avoid**: Passing Node.js `Buffer` objects directly to `ImageRun` often triggers a `SharedArrayBuffer` TypeError.
    - **Fix**: Convert the image to a Base64 string OR create a clean `Uint8Array` copy using `Buffer.copy()` before passing it to the library.
- **Image-Size**: Always pass the `Buffer` to `sizeOf()`, not the file path, to avoid internal `TextDecoder` encoding errors on certain operating systems.

## 3. Aesthetic & Content Standards
- **Prose over Data**: Never produce a manual that lists raw input names (e.g., "input_user_name").
- **Human-Centric Translation**:
    - "Name" fields -> "Primary record identifier for classification."
    - "Select" boxes -> "Standardized categorization dropdown."
    - "Date" fields -> "Chronological tracking for historical reporting."
- **Visuals**: Use Hex `#2E74B5` (Corporate Blue) for all headers and structural borders. Ensure a 1920x1080 capture resolution.



# OMNI PROTOCOL v2026.3: TWO-STEP CORPORATE AUDIT

Execute a deep audit for this at [http://localhost/testingspace3/index.php]. Use following credetails credentials.

and tenat admin login
http://localhost/testingspace3/
username is "admin" and password "admin@123"

also disable the captcha to be not required for the time bieng so that we dont have any issue with logins


## STEP 1: ASSET CAPTURE & DISCOVERY
1. **Spidering**: Log in and dynamically discover all modules in the sidebar/header.
2. **Deep Capture**: 
   - Visit every page.
   - Capture a High-DPI Full-Page Screenshot.
   - Click most important buttons to capture form modal states.
3. **GitHub-Style MD Documentation**: For every screenshot, generate a dedicated `.md` file containing:
   - Module/page Name and functional purpose and effects in app.
   - each form fields (Label, Type, and a human-readable description of their use).
   - All table extracted from the page with descriptions and details.
   - Embedded image reference.

## STEP 2: PROFESSIONAL DOCX GENERATION
1. **Source Assets**: Read the `.md` and `.png` files generated in Step 1.
2. **Design Standard**: 
   - **Modern Corporate Aesthetic**: Use Calibri/Segoe UI fonts and Hex `#2E74B5` for Headers.
   - **Narrative Flow**: Translate raw technical fields into professional prose (e.g., "Captures chronological data" instead of "Date Input").
3. **Structure**: 
   - Professional Cover Page with Version Control.
   - Auto-generated Table of Contents.
   - Module chapters featuring visual references and detailed field-by-field explanations.

## MANDATORY TECHNICAL COMPLIANCE
- Pass image buffers to `image-size` to avoid Node 24 encoding errors.
- Convert image data to Base64 or copy to a clean `Uint8Array` to satisfy `docx` v9.x requirements.
- Maintain exact aspect ratios for all embedded images.

if possible temproraly disabale the captcha feature to do the work without interferance. also plan first please

let's proceed first with step 1