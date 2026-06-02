# Safety and Trust - 安全與信任機制

## 身份驗證
- 使用 PHP Session 管理登入狀態
- 密碼使用 password_hash() (PASSWORD_DEFAULT) 雜湊
- 所有需要身份的 API 必須先驗證 session

## 權限控制
- 任務操作必須驗證 client_id 是否為當前使用者
- 指派/確認操作只有任務建立者可執行
- 開始/完成操作只有被指派的 worker 可執行

## SQL 注入防護
- 所有資料庫操作使用 PDO Prepared Statements
- 禁止直接拼接 SQL 字串

## XSS 防護
- 前端輸出資料使用 textContent 而非 innerHTML
- 後端回傳資料使用 htmlspecialchars() 處理

## CORS 設定
- 開發環境允許 localhost 跨域請求
