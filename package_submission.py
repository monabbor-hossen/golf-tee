import os
import zipfile
import sys

root = r"c:\xampp\htdocs\golf-tee"
out_zip = os.path.join(root, "GolfTee-Submission-v1.0.0.zip")

print("📦 Building GolfTee-Submission-v1.0.0.zip via Python zipfile...")

if os.path.exists(out_zip):
    os.remove(out_zip)
    print("Removed previous archive.")

exclude_dirs = {
    ".git", ".github", ".idea", ".vscode", "build", ".dart_tool",
    "node_modules", "vendor", "scratch", ".system_generated", ".cache",
    "ephemeral", ".figma"
}

exclude_extensions = {".zip", ".log", ".tmp"}
exclude_exact_files = {
    "audit_report_full.txt", "package_submission.ps1", "package_submission.py",
    "scratch_audit_sql.py", "scratch_scan_sql.py", "test_all_879_findings.py",
    "scratch_verify_all.py"
}

included_count = 0
with zipfile.ZipFile(out_zip, "w", zipfile.ZIP_DEFLATED, compresslevel=9) as zf:
    for dirpath, dirnames, filenames in os.walk(root):
        # Filter dirnames in-place
        dirnames[:] = [d for d in dirnames if d not in exclude_dirs]
        
        rel_dir = os.path.relpath(dirpath, root)
        if rel_dir == ".":
            rel_dir = ""
            
        # Also check if any component of rel_dir is in exclude_dirs
        if any(part in exclude_dirs for part in rel_dir.split(os.sep)):
            continue

        for filename in filenames:
            # Check exclusions
            if filename in exclude_exact_files:
                continue
            ext = os.path.splitext(filename)[1].lower()
            if ext in exclude_extensions:
                continue
            # Handle .env files: only .env.example is allowed!
            if filename.startswith(".env") and filename != ".env.example":
                print(f"🔒 Skipped live env file: {os.path.join(rel_dir, filename)}")
                continue

            full_path = os.path.join(dirpath, filename)
            # Create standard zip arcname with forward slashes
            if rel_dir:
                arcname = (rel_dir + "/" + filename).replace("\\", "/")
            else:
                arcname = filename

            zf.write(full_path, arcname)
            included_count += 1

print(f"✅ Successfully packaged {included_count} files into {out_zip}")
size_mb = os.path.getsize(out_zip) / (1024 * 1024)
print(f"📦 Final ZIP size: {size_mb:.2f} MB ({os.path.getsize(out_zip)} bytes)")
