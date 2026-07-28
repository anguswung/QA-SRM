# Linux 环境部署指南

## 组件安装

### 1. Poppler (PDF文本提取)
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install poppler-utils

# CentOS/RHEL
sudo yum install epel-release
sudo yum install poppler-utils
```

### 2. Tesseract OCR (扫描件识别)
```bash
# Ubuntu/Debian
sudo apt install tesseract-ocr tesseract-ocr-chi-sim tesseract-ocr-eng

# CentOS/RHEL
sudo yum install tesseract tesseract-langpack-chi-sim tesseract-langpack-eng
```

### 3. 验证安装
```bash
# 验证 Poppler
pdftotext -v
pdftoppm -v

# 验证 Tesseract
tesseract --version
tesseract --list-langs
```

## 环境配置

编辑 `.env` 文件，更新路径：
```bash
# Linux 路径
POPPLER_PDFTEXT=/usr/bin/pdftotext
POPPLER_PDFINFO=/usr/bin/pdfinfo
POPPLER_PDFPPM=/usr/bin/pdftoppm
TESSERACT_PATH=/usr/bin/tesseract
```

## PHP 配置要求

确保 PHP 已启用以下扩展：
- `exec` 函数未被禁用
- `fileinfo` 扩展
- `mbstring` 扩展

检查 `php.ini`：
```ini
; 确保 exec 未被禁用
disable_functions = exec,passthru,shell_exec,system,proc_open,popen
; 移除 exec 等函数
```

## 文件权限
```bash
# 确保 Web 服务器可以执行外部命令
chmod +x /usr/bin/tesseract
chmod +x /usr/bin/pdftotext
chmod +x /usr/bin/pdftoppm

# 确保上传目录可写
chmod -R 755 uploads/
chown -R www-data:www-data uploads/
```

## 常见问题

### Q: Tesseract 识别中文乱码
A: 确保安装了中文语言包：
```bash
sudo apt install tesseract-ocr-chi-sim
# 验证
tesseract --list-langs | grep chi
```

### Q: 扫描件识别率低
A: 建议：
- 扫描分辨率至少 300 DPI
- 确保图像清晰、对比度高
- 避免倾斜或扭曲的扫描件

### Q: exec 函数被禁用
A: 联系主机提供商或修改 php.ini
