# Testing Scenarios - 完整測試情境

## 1. 使用者註冊 (Register)

### 1.1 成功情境
- 所有欄位填寫正確 → 回傳 201, {status: "success", message: "註冊成功"}
- username: "testuser", email: "test@example.com", password: "password123", role: "worker"

### 1.2 失敗情境
- Email 已被註冊 → 回傳 400, {status: "error", message: "此 Email 已被使用"}
- Username 已被使用 → 回傳 400, {status: "error", message: "此用戶名稱已被使用"}
- 缺少必填欄位 → 回傳 400, {status: "error", message: "請填寫所有必填欄位"}
- Email 格式錯誤 → 回傳 400, {status: "error", message: "Email 格式不正確"}
- 密碼少於 6 字元 → 回傳 400, {status: "error", message: "密碼至少需要 6 個字元"}

## 2. 使用者登入 (Login)

### 2.1 成功情境
- 正確的 email + password → 回傳 200, {status: "success", data: {user: {...}}}
- Session 被建立，後續 API 可識別身分

### 2.2 失敗情境
- Email 不存在 → 回傳 401, {status: "error", message: "Email 或密碼錯誤"}
- 密碼錯誤 → 回傳 401, {status: "error", message: "Email 或密碼錯誤"}
- 缺少 email 或 password → 回傳 400, {status: "error", message: "請填寫所有必填欄位"}

## 3. 發布任務 (Create Task)

### 3.1 成功情境
- 已登入的 client 或 worker 填寫所有必填欄位 → 回傳 201, {status: "success", data: {task: {...}}}

### 3.2 失敗情境
- 未登入 → 回傳 401, {status: "error", message: "請先登入"}
- 缺少必填欄位 (title/description/budget/deadline) → 回傳 400
- budget 為負數 → 回傳 400, {status: "error", message: "預算不可為負數"}
- deadline 為過去日期 → 回傳 400, {status: "error", message: "截止日期不可為過去"}

## 4. 查看任務列表 (Get Tasks)

### 4.1 成功情境
- 任何人（含未登入）可查看 → 回傳 200, {status: "success", data: {tasks: [...]}}
- 支援 status 篩選: ?status=open
- 支援 category 篩選: ?category=設計

## 5. 申請接案 (Apply Task)

### 5.1 成功情境
- 已登入的 worker，任務狀態為 open → 回傳 200, {status: "success", message: "申請成功"}

### 5.2 失敗情境
- 未登入 → 401
- 任務建立者不可申請自己的任務 → 回傳 400, {status: "error", message: "不可申請自己發布的任務"}
- 已申請過 → 回傳 400, {status: "error", message: "您已申請過此任務"}
- 任務狀態不是 open → 回傳 400, {status: "error", message: "此任務已不開放申請"}

## 6. 指派接案者 (Assign Worker)

### 6.1 成功情境
- 任務建立者選擇一位申請者 → 任務狀態變為 assigned，回傳 200

### 6.2 失敗情境
- 非任務建立者 → 回傳 403, {status: "error", message: "無權限執行此操作"}
- 被指派者未申請此任務 → 回傳 400, {status: "error", message: "該使用者未申請此任務"}
- 任務狀態不是 open → 回傳 400

## 7. 更新任務狀態 (Update Status)

### 7.1 開始執行 (start)
- 被指派的 worker，任務狀態為 assigned → 狀態變為 in_progress
- 非被指派者 → 403
- 錯誤狀態 → 400

### 7.2 標記完成 (complete)
- 被指派的 worker，任務狀態為 in_progress → 狀態變為 completed_pending_confirmation
- 非被指派者 → 403
- 錯誤狀態 → 400

### 7.3 確認完成 (confirm)
- 任務建立者，任務狀態為 completed_pending_confirmation → 狀態變為 completed
- 非建立者 → 403
- 錯誤狀態 → 400

## 8. 修改與刪除任務

### 8.1 修改任務 (Update Task)
- 建立者，任務狀態為 open → 成功修改
- 非建立者 → 403
- 狀態不是 open → 400, {status: "error", message: "任務進行中無法修改"}

### 8.2 刪除任務 (Delete Task)
- 建立者，任務狀態為 open → 成功刪除
- 非建立者 → 403
- 已有申請者或狀態不是 open → 400, {status: "error", message: "任務已有申請者，無法刪除"}
