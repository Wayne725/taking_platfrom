# TODO - 接案系統開發清單

## Must Have (核心功能)

### 階段一：資料庫與基礎環境
- [x] 建立專案資料夾結構
- [x] 設計並建立 MySQL Schema (database.sql)
- [x] 建立 server/db.php (PDO 連線)

### 階段二：使用者系統
- [x] POST /api/auth/register - 使用者註冊
- [x] POST /api/auth/login - 使用者登入
- [x] POST /api/auth/logout - 使用者登出
- [x] GET /api/auth/me - 取得目前登入者資訊
- [x] GET /api/auth/profile - 取得個人完整資料與統計
- [x] PUT /api/auth/profile - 更新使用者名稱 / 密碼

### 階段三：任務核心 CRUD 與狀態流轉
- [x] POST /api/tasks - 發布任務
- [x] GET /api/tasks - 取得任務列表（含分頁）
- [x] GET /api/tasks/{id} - 取得單一任務（含活動記錄）
- [x] PUT /api/tasks/{id} - 修改任務（僅建立者）
- [x] DELETE /api/tasks/{id} - 刪除任務（僅建立者）
- [x] POST /api/tasks/{id}/apply - 申請接案（含留言）
- [x] POST /api/tasks/{id}/withdraw - 取消申請
- [x] POST /api/tasks/{id}/assign - 指派接案者（僅建立者）
- [x] POST /api/tasks/{id}/start - 開始執行（僅被指派者）
- [x] POST /api/tasks/{id}/complete - 標記完成（僅被指派者）
- [x] POST /api/tasks/{id}/confirm - 確認完成（僅建立者）

### 階段四：前端頁面
- [x] login.html - 登入頁面
- [x] register.html - 註冊頁面
- [x] index.html - 任務列表頁面（含分頁）
- [x] task-detail.html - 任務詳細頁面（含活動時間軸、申請留言 Modal、取消申請）
- [x] task-create.html - 建立任務頁面
- [x] dashboard.html - 個人儀表板（強化統計卡片、快速操作按鈕）
- [x] profile.html - 個人資料頁面

## Nice to Have (已完成)
- [x] 搜尋與篩選任務
- [x] 分頁功能 (page / limit)
- [x] 任務活動記錄 (task_activities)
- [x] 申請留言功能
- [x] 取消申請功能
- [x] 個人資料頁面（修改名稱、密碼）
- [x] 導覽列加入「我的資料」連結
- [x] CSS 時間軸樣式、申請 Modal 樣式、分頁樣式、個人資料樣式、Toast 樣式

## Nice to Have (待實作)
- [ ] 通知系統
- [ ] 評分系統
- [ ] 上傳附件功能
