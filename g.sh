#!/bin/bash

echo "🔧 Git Auto Sync Script"

# Check Git install
if ! command -v git &>/dev/null; then
    echo "❌ Git is not installed. Install it first."
    exit 1
fi

# Ask for Git config if not set
GIT_NAME=$(git config --global user.name)
GIT_EMAIL=$(git config --global user.email)

if [ -z "$GIT_NAME" ] || [ -z "$GIT_EMAIL" ]; then
    read -p "🧑 Enter your GitHub name: " name
    read -p "📧 Enter your GitHub email: " email
    git config --global user.name "$name"
    git config --global user.email "$email"
    echo "✅ Git config set"
fi

# Init git if not already
if [ ! -d .git ]; then
    echo "📦 Initializing Git repo..."
    git init
fi

# .gitignore setup
if [ ! -f .gitignore ]; then
    echo "📝 Creating .gitignore..."
    cat <<EOL > .gitignore
/vendor/
/node_modules/
/storage/*.key
.DS_Store
.idea
*.log
EOL
fi

# Ask for remote if not set
if ! git remote get-url origin &>/dev/null; then
    echo "🌐 No remote found."
    read -p "Enter your GitHub SSH repo URL (e.g. git@github.com:user/repo.git): " REMOTE
    git remote add origin "$REMOTE"
fi

# Stage everything, including .env
echo "📂 Staging all files..."
git add -A

# Commit
read -p "💬 Commit message: " MSG
if [ -z "$MSG" ]; then
    MSG="update on $(date)"
fi
git commit -m "$MSG"

# Pull latest changes before push
echo "🔄 Pulling latest changes from origin..."
git pull origin main --rebase

# Push
echo "🚀 Pushing to GitHub..."
git push origin main

echo "✅ Done! Synced with GitHub."
