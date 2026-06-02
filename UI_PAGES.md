# UI Pages 設計規格

## 1. Login Page (login.html)
**路徑**: /login.html
**功能**: 使用者登入
**欄位**:
- email (text input, required)
- password (password input, required)
- 登入按鈕
- 前往註冊連結

**行為**:
- 成功登入 → 導向 index.html
- 失敗 → 顯示錯誤訊息

## 2. Register Page (register.html)
**路徑**: /register.html
**功能**: 新使用者註冊
**欄位**:
- username (text input, required, min 3 chars)
- email (email input, required)
- password (password input, required, min 6 chars)
- confirm_password (password input, required)
- role: client (發案者) / worker (接案者)
- 註冊按鈕
- 前往登入連結

**行為**:
- 成功註冊 → 導向 login.html
- 失敗 → 顯示錯誤訊息

## 3. Task List Page (index.html)
**路徑**: /index.html
**功能**: 瀏覽所有公開任務
**欄位顯示**:
- 任務標題
- 任務描述（截斷）
- 預算
- 截止日期
- 狀態 badge
- 建立者名稱
- 申請人數
**操作**:
- 點擊任務 → 前往 task-detail.html
- 建立任務按鈕（登入後顯示）
- 登出按鈕

## 4. Task Detail Page (task-detail.html)
**路徑**: /task-detail.html?id={task_id}
**功能**: 查看任務詳情、進行申請/指派/狀態操作
**欄位顯示**:
- 所有任務資訊
- 申請者列表（僅建立者可見）
- 目前指派者
- 狀態歷程

**操作按鈕（依角色與狀態動態顯示）**:
- 申請接案（worker, 狀態=open）
- 指派接案者（client/建立者, 狀態=open, 選擇申請者）
- 開始執行（worker/被指派者, 狀態=assigned）
- 標記完成（worker/被指派者, 狀態=in_progress）
- 確認完成（client/建立者, 狀態=completed_pending_confirmation）
- 修改任務（建立者, 狀態=open）
- 刪除任務（建立者, 狀態=open）

## 5. Task Create Page (task-create.html)
**路徑**: /task-create.html
**功能**: 發布新任務
**欄位**:
- title (text, required)
- description (textarea, required)
- budget (number, required, min 0)
- deadline (date, required)
- category (select: 設計/開發/文案/行銷/其他)
- 發布按鈕

## 6. Dashboard Page (dashboard.html)
**路徑**: /dashboard.html
**功能**: 個人任務管理
**顯示**:
- 我發布的任務（client 視角）
- 我接的任務（worker 視角）
- 統計資訊

## Task 資料模型
```json
{
  "id": 1,
  "title": "設計 LOGO",
  "description": "需要設計一個現代感的品牌 LOGO",
  "budget": 5000,
  "deadline": "2024-12-31",
  "category": "設計",
  "status": "open",
  "client_id": 1,
  "client_name": "Wayne",
  "assigned_worker_id": null,
  "assigned_worker_name": null,
  "applicant_count": 3,
  "created_at": "2024-01-01 00:00:00"
}
```
