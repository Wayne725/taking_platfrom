# 專案開發指南

## 技術棧 (Tech Stack)
- **前端**：HTML5, CSS3 (可使用 Tailwind CSS CDN 或純 CSS), 原生 JavaScript (Fetch API)
- **後端**：PHP 8+ (原生或微型框架)
- **資料庫**：MySQL
- **架構**：前後端分離，PHP 負責提供 RESTful API，前端透過 Fetch 呼叫。

## 程式碼風格與原則
- **資料庫操作**：必須使用 PDO (PHP Data Objects) 並全面使用 Prepared Statements (預處理語句) 來防止 SQL Injection。
- **API 回傳格式**：統一使用 JSON 格式回傳，包含 `status` (success/error), `message`, 與 `data` 欄位。
- **權限與驗證**：所有的 API 都必須嚴格檢查使用者權限（例如：發案者只能管理自己的任務，只有被指派者能更新進度），參考 `SAFETY_AND_TRUST.md` 與 `TESTING_SCENARIOS.md`。
- **狀態管理**：嚴格遵守 `TESTING_SCENARIOS.md` 中定義的任務狀態流 (open -> assigned -> in_progress -> completed_pending_confirmation -> completed)。

## 開發流程
1. 開發前務必閱讀 `TODO.md` 確認當前進度。
2. 開發 API 時，請對照 `TESTING_SCENARIOS.md` 確保所有邊界條件（如重複申請、錯誤密碼等）都有被處理。
3. 每個階段完成後，請主動幫我更新 `TODO.md` 中的打勾狀態 `[x]`。