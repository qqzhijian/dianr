#!/bin/bash

echo "=== 点燃平台部署检查脚本 ==="
echo

# 检查PHP
echo "1. 检查PHP版本..."
if command -v php &> /dev/null; then
    php_version=$(php -r "echo PHP_VERSION;")
    echo "✓ PHP版本: $php_version"
else
    echo "✗ PHP未安装"
fi

# 检查文件权限
echo
echo "2. 检查文件权限..."
if [ -w "/workspaces/dianr" ]; then
    echo "✓ 项目目录可写"
else
    echo "✗ 项目目录权限不足"
fi

# 检查数据库文件
echo
echo "3. 检查数据库文件..."
if [ -f "/workspaces/dianr/database.sql" ]; then
    db_size=$(stat -c%s "/workspaces/dianr/database.sql")
    echo "✓ database.sql存在 ($db_size bytes)"
else
    echo "✗ database.sql不存在"
fi

# 检查配置文件
echo
echo "4. 检查配置文件..."
if [ -f "/workspaces/dianr/config/config.php" ]; then
    echo "✓ 配置文件存在"
else
    echo "✗ 配置文件不存在"
fi

# 检查核心文件
echo
echo "5. 检查核心文件..."
core_files=("index.php" "login.php" "register.php" "admin.php" "reviews.php" "certifications.php")
for file in "${core_files[@]}"; do
    if [ -f "/workspaces/dianr/$file" ]; then
        echo "✓ $file 存在"
    else
        echo "✗ $file 不存在"
    fi
done

# 检查API文件
echo
echo "6. 检查API文件..."
api_files=("messages.php" "send_message.php" "verify_user.php")
for file in "${api_files[@]}"; do
    if [ -f "/workspaces/dianr/api/$file" ]; then
        echo "✓ api/$file 存在"
    else
        echo "✗ api/$file 不存在"
    fi
done

echo
echo "=== 检查完成 ==="
echo "请根据上述结果进行相应的配置和部署。"