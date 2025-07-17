# Auto Alt Text 自動 Alt 文字

> **Fork 聲明:** 這是原始「Auto Alt Text」WordPress 外掛的 fork 版本，原作者為 [Valerio Monti](https://www.vmweb.it)。
>
> - **原始儲存庫:** https://github.com/valeriomonti/auto-alt-text
> - **Fork 維護者:** [Fuyuan Cheng](https://github.com/gloomcheng)
> - **目前版本:** 2.4.3 (fork 版本)
> - **授權:** GPL v3 (不變)
>
> 此 fork 版本與原始外掛完全相容，同時依照 GPL v3 要求保留所有原作者歸屬。

這個進階的 WordPress 外掛使用尖端 AI 技術，自動為上傳到媒體庫的圖片生成 Alt 文字。您可以選擇多個 AI 提供者，包括 OpenAI、Azure 和 HuggingFace，實現經濟實惠的多語言 alt 文字生成。

## ✨ v2.4.3 新功能

### 🚀 HuggingFace AI 整合
- **LlavaVision 模型**: 具有上下文感知能力的直接圖像分析
- **Phi-4 文字模型**: 進階文字型 alt 文字生成
- **經濟實惠**: OpenAI/Azure 的免費且實惠替代方案
- **上下文感知**: 考慮文章標題、內容、分類和標籤

### 🌍 增強的多語言支援
- **15+ 語言**: 包括繁體中文、簡體中文、日文、韓文和歐洲語言
- **直接生成**: AI 直接以目標語言生成 alt 文字（無需翻譯）
- **完整本地化**: 全面的繁體中文 (zh_TW) 介面

### ⚙️ 進階配置
- **靈活提供者**: 分離的文字和視覺模型配置
- **多重 API 金鑰**: 不同提供者的個別管理
- **智能檢測**: 從文章編輯器、媒體庫等地方感知上下文

## 🎯 功能特色

此外掛支援多種 alt 文字生成方法：

### AI 驅動的方法
- **OpenAI APIs** (GPT-4o, GPT-4o Mini, o1 Mini) - 高級 AI 視覺模型
- **HuggingFace APIs** (LlavaVision, Phi-4) - 經濟實惠的開源模型
- **Azure 電腦視覺** - 企業級圖像分析，支援翻譯

### 非 AI 方法
- **文章標題** - 使用包含圖片的文章標題
- **附件標題** - 使用圖片的檔名/標題

## 🛠️ 系統需求

- **PHP**: >= 7.4
- **WordPress**: >= 6.0
- **Node.js**: 18+
- **Composer**: 用於依賴管理
- **npm**: 用於資源建置

## 🚀 安裝步驟

1. **複製儲存庫:**
```bash
git clone git@github.com:gloomcheng/auto-alt-text.git
cd auto-alt-text
```

2. **安裝依賴:**
```bash
composer install
npm install
npm run build
```

3. **啟用外掛:**
   - 上傳到您的 WordPress `/wp-content/plugins/` 目錄
   - 在 WordPress 管理員後台的外掛中啟用
   - 在設定 → Auto Alt Text Options 中配置

## 🔧 配置說明

### OpenAI 設定
1. 訪問 [OpenAI API](https://platform.openai.com/api-keys)
2. 創建帳戶並添加付費資訊
3. 生成 API 金鑰
4. 在外掛設定中輸入金鑰

### HuggingFace 設定（推薦經濟實惠選項）
1. 訪問 [HuggingFace](https://huggingface.co/settings/tokens)
2. 註冊免費帳戶
3. 創建具有「讀取」權限的新存取權杖
4. 在外掛設定中輸入權杖

### Azure 電腦視覺設定
1. 創建 Azure 帳戶和電腦視覺資源
2. 獲取您的 API 金鑰和端點
3. 可選設定 Azure 翻譯器以支援多語言
4. 在外掛設定中配置

## 💡 運作原理

### 自動生成
配置完成後，上傳圖片時會自動生成 alt 文字。AI 會考慮：
- **圖像內容**: 實際圖像的視覺分析
- **上下文**: 文章標題、內容、分類和標籤
- **語言**: 直接以您選擇的語言生成

### 批量生成
對於現有圖片：
1. 前往媒體庫（列表檢視）
2. 選擇需要 alt 文字的圖片
3. 選擇「生成 alt 文字」批量操作
4. 等待處理（時間因圖片數量/大小而異）

### 個別生成
對於單一圖片：
1. 開啟媒體庫（網格檢視）
2. 選擇一張圖片
3. 點擊「生成 alt 文字」按鈕
4. Alt 文字立即更新

## 🌐 語言支援

外掛現在支援 15+ 種語言的直接 alt 文字生成：
- **英文** (en)
- **繁體中文** (zh-tw) 繁體中文
- **簡體中文** (zh) 简体中文
- **日文** (ja) 日本語
- **韓文** (ko) 한국어
- **西班牙文** (es)
- **法文** (fr)
- **德文** (de)
- **義大利文** (it)
- **葡萄牙文** (pt)
- **俄文** (ru)
- **阿拉伯文** (ar)
- **印地文** (hi)
- **泰文** (th)
- **越南文** (vi)

## 🔒 安全性與加密

### 增強的 API 金鑰保護
我們強烈建議在您的 `wp-config.php` 中定義外掛特定的加密常數：

```php
define( 'AAT_ENCRYPTION_KEY',  'a_random_string_of_at_least_64_characters' );
define( 'AAT_ENCRYPTION_SALT', 'another_random_string_of_at_least_64_characters' );
```

這些常數會在外掛設定中自動生成 - 只需複製並貼上到您的 `wp-config.php` 中 `/* That's all, stop editing! Happy publishing. */` 行之前即可。

## 📊 錯誤日誌

外掛包含完整的錯誤日誌功能：
- 失敗的 API 呼叫會記錄到自訂資料庫表
- 在 Auto Alt Text → 錯誤日誌中查看日誌
- 即使 alt 文字生成失敗，圖片也會成功上傳
- 不會影響您的編輯工作流程

## ⚠️ 重要注意事項

### 效能考量
- **API 方法**: 由於外部 API 呼叫，上傳時間可能會增加
- **逾時**: 所有 API 請求有 90 秒逾時
- **備用方案**: 即使 alt 文字生成失敗，圖片也會成功上傳

### 上下文需求
- 文章標題方法：上傳圖片前先將文章儲存為草稿
- AI 方法：確保穩定的網路連線
- 最佳效果：提供清晰、描述性的圖像上下文

## 🔄 從原始外掛遷移

此 fork 版本與原始外掛完全相容。您現有的設定和 API 金鑰將繼續運作，無需任何變更。

## 🆘 疑難排解

### 常見問題
1. **API 金鑰錯誤**: 檢查錯誤日誌以獲得特定的 API 回應
2. **缺少 Alt 文字**: 驗證 API 金鑰並檢查錯誤日誌
3. **逾時問題**: 大型圖片可能需要更長的處理時間
4. **上下文問題**: 上傳圖片前先將文章儲存為草稿

### 獲得幫助
- 檢查外掛設定中的錯誤日誌
- 驗證 API 金鑰是否正確輸入
- 確保已為付費 API（OpenAI、Azure）設定付費
- 先用較小的圖片進行測試

## 📄 授權

此外掛採用 GPL v3 授權，與原始作品保持相同的授權。您可以在 GPL v3 授權條款下自由使用、修改和分發此外掛。

## 🙏 致謝

- **原作者**: [Valerio Monti](https://www.vmweb.it)
- **原始儲存庫**: https://github.com/valeriomonti/auto-alt-text
- **Fork 維護者**: [Fuyuan Cheng](https://github.com/gloomcheng)

此 fork 版本在保持對原作者工作的完全尊重和 GPL v3 授權要求的同時，添加了重要的增強功能。
