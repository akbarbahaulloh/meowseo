#!/bin/bash
# Build script untuk MeowSEO release

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}Building MeowSEO Release...${NC}"

# 1. Run tests first
echo -e "${BLUE}Running tests...${NC}"
composer test
if [ $? -ne 0 ]; then
    echo "Tests failed! Aborting release."
    exit 1
fi

npm test
if [ $? -ne 0 ]; then
    echo "JavaScript tests failed! Aborting release."
    exit 1
fi

echo -e "${GREEN}✅ All tests passed!${NC}"

# 2. Build assets
echo -e "${BLUE}Building assets...${NC}"
npm run build

# 3. Install production dependencies only
echo -e "${BLUE}Installing production dependencies...${NC}"
composer install --no-dev --optimize-autoloader

# 4. Create release directory
RELEASE_DIR="meowseo-release"
rm -rf $RELEASE_DIR
mkdir -p $RELEASE_DIR

# 5. Copy files (exclude tests and dev files)
echo -e "${BLUE}Copying files...${NC}"
rsync -av --progress . $RELEASE_DIR \
    --exclude='.git' \
    --exclude='.github' \
    --exclude='.claude' \
    --exclude='tests' \
    --exclude='node_modules' \
    --exclude='src' \
    --exclude='vendor' \
    --exclude='*.log' \
    --exclude='*.md' \
    --exclude='phpunit.xml' \
    --exclude='.phpunit.result.cache' \
    --exclude='jest.config.js' \
    --exclude='composer.json' \
    --exclude='composer.lock' \
    --exclude='package.json' \
    --exclude='package-lock.json' \
    --exclude='webpack.config.js' \
    --exclude='tsconfig.json' \
    --exclude='.vscode' \
    --exclude='.idea'

# 6. Copy README.md (keep this one)
cp README.md $RELEASE_DIR/

# 7. Create ZIP
echo -e "${BLUE}Creating ZIP file...${NC}"
cd $RELEASE_DIR
zip -r ../meowseo.zip . -q
cd ..

# 8. Reinstall dev dependencies
echo -e "${BLUE}Reinstalling dev dependencies...${NC}"
composer install

echo -e "${GREEN}✅ Release built successfully!${NC}"
echo -e "${GREEN}📦 File: meowseo.zip${NC}"
ls -lh meowseo.zip
