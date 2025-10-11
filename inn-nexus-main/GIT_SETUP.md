# Git Configuration for Inn Nexus Project

## Fix Line Ending Warnings

The warnings you're seeing are about line ending conversions between LF (Linux/Mac) and CRLF (Windows). Here's how to fix them:

### 1. Configure Git Line Endings
```bash
# Set Git to handle line endings automatically
git config core.autocrlf true

# Or for cross-platform projects, use input mode
git config core.autocrlf input

# Set core.safecrlf to warn about irreversible conversions
git config core.safecrlf warn
```

### 2. Clean Up Current Repository
```bash
# Remove all files from Git index
git rm --cached -r .

# Re-add all files with proper line endings
git add .

# Commit the changes
git commit -m "Fix line endings and clean up repository"
```

### 3. Alternative: Reset and Re-add
```bash
# If you want to start fresh
git reset --hard HEAD
git add .
git commit -m "Initial commit with proper line endings"
```

## Complete Git Setup Commands

```bash
# Navigate to your project directory
cd inn-nexus-main

# Initialize Git repository
git init

# Configure Git settings
git config user.name "Your Name"
git config user.email "your.email@example.com"
git config core.autocrlf true
git config core.safecrlf warn

# Add all files
git add .

# Check status (should show files to be committed)
git status

# Commit initial version
git commit -m "Initial commit: Inn Nexus Hotel Management System

Features:
- Complete hotel management system
- Two-factor authentication
- Responsive design with Tailwind CSS
- Real-time room management
- Billing and payment processing
- Security features and audit logging"

# Create GitHub repository and push
git remote add origin https://github.com/YOUR_USERNAME/inn-nexus.git
git branch -M main
git push -u origin main
```

## Recommended .gitattributes File

Create a `.gitattributes` file to ensure consistent line endings:

```
# Set default behavior to automatically normalize line endings
* text=auto

# Explicitly declare text files you want to always be normalized
*.php text
*.js text
*.css text
*.html text
*.md text
*.json text
*.sql text
*.xml text
*.yml text
*.yaml text

# Declare files that will always have CRLF line endings
*.bat text eol=crlf
*.cmd text eol=crlf

# Declare files that will always have LF line endings
*.sh text eol=lf

# Denote all files that are truly binary and should not be modified
*.png binary
*.jpg binary
*.jpeg binary
*.gif binary
*.ico binary
*.svg binary
*.woff binary
*.woff2 binary
*.ttf binary
*.eot binary
*.pdf binary
*.zip binary
*.tar.gz binary
```

## Troubleshooting

### If warnings persist:
```bash
# Check current Git configuration
git config --list | grep crlf

# Force refresh all files
git add --renormalize .
git commit -m "Normalize line endings"
```

### For VS Code specific files:
```bash
# Add VS Code files to .gitignore
echo ".vscode/" >> .gitignore
echo "*.code-workspace" >> .gitignore
```

## Final Repository Structure

After setup, your repository should have:
- Clean commit history
- Proper line endings
- All project files included
- Professional README and documentation
- MIT License
- Proper .gitignore and .gitattributes

This will give you a clean, professional GitHub repository for your Inn Nexus project!
