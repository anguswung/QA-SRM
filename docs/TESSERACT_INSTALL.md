# Tesseract OCR 安装指南

## 什么是 Tesseract OCR？
Tesseract 是一个开源的光学字符识别(OCR)引擎，可以识别扫描件 PDF 中的文字。

## 安装步骤

### 1. 下载 Tesseract
访问 https://github.com/UB-Mannheim/tesseract/wiki
下载最新版本的安装程序 (tesseract-ocr-w64-setup-*.exe)

### 2. 安装 Tesseract
1. 运行安装程序
2. 选择安装路径: `D:\QA-SRM\tesseract`
3. **重要**: 在组件选择页面，勾选以下语言包:
   - Chinese Simplified (简体中文)
   - Chinese Traditional (繁体中文)
   - English (英文)
4. 完成安装

### 3. 验证安装
打开命令提示符，运行:
```cmd
D:\QA-SRM\tesseract\tesseract.exe --version
```

应该显示版本信息，如: `tesseract v5.4.0.20240606`

## 工作原理

系统处理 PDF 的优先级:
1. **Poppler pdftotext** - 提取文字型 PDF (最快)
2. **Smalot PDFParser** - 备用文字提取
3. **Tesseract OCR** - 识别扫描件 PDF (最慢但支持图片)

## 注意事项

- 扫描件 PDF 需要较长处理时间 (每页约 2-5 秒)
- 识别准确率取决于扫描件质量和字体清晰度
- 建议扫描件分辨率至少 300 DPI
