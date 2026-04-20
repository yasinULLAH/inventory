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
