/**
 * 習い事種別管理 JavaScript
 */
class CourseCategoryManager {
    constructor() {
        this.editingParentId = null;
        this.editingCategoryId = null;
        this.editingParentCategoryId = null;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        // イベント委譲でクリックを一括処理（動的要素・キャッシュ対策）
        document.body.addEventListener('click', (e) => {
            const addParent = e.target.closest('#addParentCategoryBtn, #addFirstParentCategoryBtn');
            if (addParent) {
                e.preventDefault();
                this.showParentCategoryModal();
                return;
            }
            const editParentBtn = e.target.closest('.edit-parent-btn');
            if (editParentBtn) {
                e.preventDefault();
                this.editParentCategory(e);
                return;
            }
            const deleteParentBtn = e.target.closest('.delete-parent-btn');
            if (deleteParentBtn) {
                e.preventDefault();
                this.deleteParentCategory(e);
                return;
            }
            const addCategoryBtn = e.target.closest('.add-category-btn');
            if (addCategoryBtn) {
                e.preventDefault();
                this.showCategoryModal(e);
                return;
            }
            const editCategoryBtn = e.target.closest('.edit-category-btn');
            if (editCategoryBtn) {
                e.preventDefault();
                this.editCategory(e);
                return;
            }
            const deleteCategoryBtn = e.target.closest('.delete-category-btn');
            if (deleteCategoryBtn) {
                e.preventDefault();
                this.deleteCategory(e);
                return;
            }
        });
        
        // モーダル関連
        document.getElementById('cancelParentBtn')?.addEventListener('click', () => this.hideParentCategoryModal());
        document.getElementById('cancelCategoryBtn')?.addEventListener('click', () => this.hideCategoryModal());
        
        // フォーム送信
        document.getElementById('parentCategoryForm')?.addEventListener('submit', (e) => this.saveParentCategory(e));
        document.getElementById('categoryForm')?.addEventListener('submit', (e) => this.saveCategory(e));
        
        // モーダル外クリックで閉じる
        document.getElementById('parentCategoryModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'parentCategoryModal' || e.target.classList.contains('bg-gray-600')) {
                this.hideParentCategoryModal();
            }
        });
        document.getElementById('categoryModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'categoryModal' || e.target.classList.contains('bg-gray-600')) {
                this.hideCategoryModal();
            }
        });
    }

    // 親分類モーダル表示
    showParentCategoryModal(editMode = false, parentId = null, parentName = '', parentSortOrder = '') {
        this.editingParentId = editMode ? parentId : null;

        const parentModalTitle = document.getElementById('parentModalTitle');
        if (parentModalTitle) parentModalTitle.textContent = editMode ? '親分類編集' : '親分類追加';
        const parentCategoryNameEl = document.getElementById('parentCategoryName');
        if (parentCategoryNameEl) parentCategoryNameEl.value = parentName ?? '';
        const sortOrderEl = document.getElementById('parentSortOrder');
        let hasSortOrder = false;
        if (sortOrderEl) {
            hasSortOrder = editMode && parentSortOrder != null && parentSortOrder !== '';
            if (hasSortOrder) {
                sortOrderEl.value = String(parentSortOrder);
                sortOrderEl.placeholder = '';
            } else {
                sortOrderEl.value = '';
                sortOrderEl.placeholder = '未入力の場合は末尾に追加';
            }
        }
        const saveParentBtn = document.getElementById('saveParentBtn');
        if (saveParentBtn) {
            saveParentBtn.innerHTML = editMode ?
                '<i class="fas fa-save mr-2"></i>更新' :
                '<i class="fas fa-save mr-2"></i>保存';
        }

        this.clearErrors('parent');

        const modal = document.getElementById('parentCategoryModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.style.setProperty('display', 'flex', 'important');
            modal.style.setProperty('position', 'fixed', 'important');
            modal.style.setProperty('top', '0', 'important');
            modal.style.setProperty('left', '0', 'important');
            modal.style.setProperty('right', '0', 'important');
            modal.style.setProperty('bottom', '0', 'important');
            modal.style.setProperty('z-index', '9999', 'important');
            modal.style.setProperty('align-items', 'center', 'important');
            modal.style.setProperty('justify-content', 'center', 'important');
            modal.style.setProperty('background-color', 'rgba(107, 114, 128, 0.15)', 'important');
            modal.style.setProperty('backdrop-filter', 'blur(1px)', 'important');
        }

        if (parentCategoryNameEl) parentCategoryNameEl.focus();
    }

    hideParentCategoryModal() {
        const modal = document.getElementById('parentCategoryModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
        document.getElementById('parentCategoryForm').reset();
        this.editingParentId = null;
    }

    // 分類モーダル表示
    showCategoryModal(e = null, editMode = false, categoryId = null, categoryName = '', categorySortOrder = '') {
        if (!editMode && e) {
            this.editingParentCategoryId = e.target.closest('[data-parent-id]').dataset.parentId;
        }

        this.editingCategoryId = editMode ? categoryId : null;

        const categoryModalTitle = document.getElementById('categoryModalTitle');
        if (categoryModalTitle) categoryModalTitle.textContent = editMode ? '分類編集' : '分類追加';
        const categoryNameEl = document.getElementById('categoryName');
        if (categoryNameEl) categoryNameEl.value = categoryName;
        const sortOrderEl = document.getElementById('categorySortOrder');
        let hasSortOrder = false;
        if (sortOrderEl) {
            hasSortOrder = editMode && categorySortOrder != null && categorySortOrder !== '';
            if (hasSortOrder) {
                sortOrderEl.value = String(categorySortOrder);
                sortOrderEl.placeholder = '';
            } else {
                sortOrderEl.value = '';
                sortOrderEl.placeholder = '未入力の場合は末尾に追加';
            }
        }
        const saveCategoryBtn = document.getElementById('saveCategoryBtn');
        if (saveCategoryBtn) {
            saveCategoryBtn.innerHTML = editMode ?
                '<i class="fas fa-save mr-2"></i>更新' :
                '<i class="fas fa-save mr-2"></i>保存';
        }

        this.clearErrors('category');

        const modal = document.getElementById('categoryModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.style.setProperty('display', 'flex', 'important');
            modal.style.setProperty('position', 'fixed', 'important');
            modal.style.setProperty('top', '0', 'important');
            modal.style.setProperty('left', '0', 'important');
            modal.style.setProperty('right', '0', 'important');
            modal.style.setProperty('bottom', '0', 'important');
            modal.style.setProperty('z-index', '9999', 'important');
            modal.style.setProperty('align-items', 'center', 'important');
            modal.style.setProperty('justify-content', 'center', 'important');
            modal.style.setProperty('background-color', 'rgba(107, 114, 128, 0.15)', 'important');
            modal.style.setProperty('backdrop-filter', 'blur(1px)', 'important');
        }

        if (categoryNameEl) categoryNameEl.focus();
    }

    hideCategoryModal() {
        const modal = document.getElementById('categoryModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
        document.getElementById('categoryForm').reset();
        this.editingCategoryId = null;
        this.editingParentCategoryId = null;
    }

    // 親分類編集
    editParentCategory(e) {
        const btn = e.target.closest('.edit-parent-btn');
        if (!btn) return;
        const parentId = btn.dataset.parentId;
        const parentName = btn.dataset.parentName;
        const parentSortOrder = btn.getAttribute('data-parent-sort-order') ?? '';
        this.showParentCategoryModal(true, parentId, parentName, parentSortOrder);
    }

    // 分類編集
    editCategory(e) {
        const btn = e.target.closest('.edit-category-btn');
        if (!btn) return;
        const categoryId = btn.dataset.categoryId;
        const categoryName = btn.dataset.categoryName;
        const categorySortOrder = btn.getAttribute('data-category-sort-order') ?? '';
        this.showCategoryModal(null, true, categoryId, categoryName, categorySortOrder);
    }

    // 親分類保存
    async saveParentCategory(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const name = formData.get('name').trim();
        const sortOrderRaw = formData.get('sort_order');
        const sortOrder = sortOrderRaw !== '' && sortOrderRaw !== null ? parseInt(sortOrderRaw, 10) : null;

        if (!name) {
            this.showFieldError('parentNameError', '親分類名を入力してください。');
            return;
        }
        const sortOrderInvalid = this.editingParentId && (sortOrder === null || isNaN(sortOrder) || sortOrder < 0);
        if (sortOrderInvalid) {
            this.showFieldError('parentSortOrderError', '並び順は0以上の整数を入力してください。');
            return;
        }

        try {
            const url = this.editingParentId ?
                `/admin/course-categories/parent-categories/${this.editingParentId}` :
                '/admin/course-categories/parent-categories';

            const method = this.editingParentId ? 'PUT' : 'POST';

            const body = { name: name };
            if (this.editingParentId) {
                body.sort_order = sortOrder;
            } else if (sortOrder !== null && !isNaN(sortOrder) && sortOrder >= 0) {
                body.sort_order = sortOrder;
            }

            const response = await this.fetchWithCSRF(url, {
                method: method,
                body: JSON.stringify(body)
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert('成功', result.message, 'success');
                this.hideParentCategoryModal();

                setTimeout(() => window.location.reload(), 1000);
            } else {
                if (result.errors && result.errors.name) {
                    this.showFieldError('parentNameError', result.errors.name[0]);
                }
                if (result.errors && result.errors.sort_order) {
                    this.showFieldError('parentSortOrderError', result.errors.sort_order[0]);
                }
                if (!result.success && !(result.errors && (result.errors.name || result.errors.sort_order))) {
                    this.showAlert('エラー', result.message, 'error');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('エラー', '通信エラーが発生しました。', 'error');
        }
    }

    // 分類保存
    async saveCategory(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const name = formData.get('name').trim();
        const sortOrderRaw = formData.get('sort_order');
        const sortOrder = sortOrderRaw !== '' && sortOrderRaw !== null ? parseInt(sortOrderRaw, 10) : null;

        if (!name) {
            this.showFieldError('categoryNameError', '分類名を入力してください。');
            return;
        }
        const sortOrderInvalid = this.editingCategoryId && (sortOrder === null || isNaN(sortOrder) || sortOrder < 0);
        if (sortOrderInvalid) {
            this.showFieldError('categorySortOrderError', '並び順は0以上の整数を入力してください。');
            return;
        }

        try {
            const url = this.editingCategoryId ?
                `/admin/course-categories/categories/${this.editingCategoryId}` :
                '/admin/course-categories/categories';

            const method = this.editingCategoryId ? 'PUT' : 'POST';

            const requestData = { name: name };
            if (!this.editingCategoryId) {
                requestData.parent_category_id = this.editingParentCategoryId;
            }
            if (this.editingCategoryId) {
                requestData.sort_order = sortOrder;
            } else if (sortOrder !== null && !isNaN(sortOrder) && sortOrder >= 0) {
                requestData.sort_order = sortOrder;
            }

            const response = await this.fetchWithCSRF(url, {
                method: method,
                body: JSON.stringify(requestData)
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert('成功', result.message, 'success');
                this.hideCategoryModal();

                setTimeout(() => window.location.reload(), 1000);
            } else {
                if (result.errors && result.errors.name) {
                    this.showFieldError('categoryNameError', result.errors.name[0]);
                }
                if (result.errors && result.errors.sort_order) {
                    this.showFieldError('categorySortOrderError', result.errors.sort_order[0]);
                }
                if (!result.success && !(result.errors && (result.errors.name || result.errors.sort_order))) {
                    this.showAlert('エラー', result.message, 'error');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('エラー', '通信エラーが発生しました。', 'error');
        }
    }

    // 親分類削除
    async deleteParentCategory(e) {
        const btn = e.target.closest('.delete-parent-btn');
        const parentId = btn.dataset.parentId;
        const parentName = btn.dataset.parentName;
        
        if (!confirm(`親分類「${parentName}」を無効化しますか？\n子分類も同時に無効化されます。`)) {
            return;
        }

        try {
            const response = await this.fetchWithCSRF(`/admin/course-categories/parent-categories/${parentId}`, {
                method: 'DELETE'
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert('成功', result.message, 'success');
                
                // 該当の親分類要素を削除
                setTimeout(() => {
                    document.querySelector(`[data-parent-id="${parentId}"]`)?.remove();
                }, 1000);
            } else {
                this.showAlert('エラー', result.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('エラー', '通信エラーが発生しました。', 'error');
        }
    }

    // 分類削除
    async deleteCategory(e) {
        const btn = e.target.closest('.delete-category-btn');
        const categoryId = btn.dataset.categoryId;
        const categoryName = btn.dataset.categoryName;
        
        if (!confirm(`分類「${categoryName}」を無効化しますか？`)) {
            return;
        }

        try {
            const response = await this.fetchWithCSRF(`/admin/course-categories/categories/${categoryId}`, {
                method: 'DELETE'
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert('成功', result.message, 'success');
                
                // 該当の分類要素を削除
                setTimeout(() => {
                    document.querySelector(`[data-category-id="${categoryId}"]`)?.remove();
                }, 1000);
            } else {
                this.showAlert('エラー', result.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('エラー', '通信エラーが発生しました。', 'error');
        }
    }

    // CSRF トークン付きfetch
    async fetchWithCSRF(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        return fetch(url, { ...defaultOptions, ...options });
    }

    // エラー表示クリア
    clearErrors(type) {
        if (type === 'parent') {
            document.getElementById('parentNameError').classList.add('hidden');
            document.getElementById('parentNameError').textContent = '';
            const parentSortOrderError = document.getElementById('parentSortOrderError');
            if (parentSortOrderError) {
                parentSortOrderError.classList.add('hidden');
                parentSortOrderError.textContent = '';
            }
        } else if (type === 'category') {
            document.getElementById('categoryNameError').classList.add('hidden');
            document.getElementById('categoryNameError').textContent = '';
            const categorySortOrderError = document.getElementById('categorySortOrderError');
            if (categorySortOrderError) {
                categorySortOrderError.classList.add('hidden');
                categorySortOrderError.textContent = '';
            }
        }
    }

    // フィールドエラー表示
    showFieldError(elementId, message) {
        const errorElement = document.getElementById(elementId);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
        }
    }

    // アラート表示
    showAlert(title, message, type = 'info') {
        // 簡単なアラート実装（実際のプロジェクトではより高機能なライブラリを使用）
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'error' ? 'alert-error' : 'alert-info';
        
        // 既存のアラートを削除
        const existingAlert = document.querySelector('.custom-alert');
        if (existingAlert) {
            existingAlert.remove();
        }

        // 新しいアラートを作成
        const alertDiv = document.createElement('div');
        alertDiv.className = `custom-alert fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 max-w-sm ${alertClass}`;
        alertDiv.innerHTML = `
            <div class="flex items-center">
                <div class="flex-1">
                    <h4 class="font-medium">${title}</h4>
                    <p class="text-sm mt-1">${message}</p>
                </div>
                <button class="ml-4 text-white" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        // アラートのスタイル
        if (type === 'success') {
            alertDiv.style.backgroundColor = '#10b981';
            alertDiv.style.color = 'white';
        } else if (type === 'error') {
            alertDiv.style.backgroundColor = '#ef4444';
            alertDiv.style.color = 'white';
        } else {
            alertDiv.style.backgroundColor = '#3b82f6';
            alertDiv.style.color = 'white';
        }

        document.body.appendChild(alertDiv);

        // 5秒後に自動削除
        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

// DOM読み込み完了後に初期化
document.addEventListener('DOMContentLoaded', function() {
    new CourseCategoryManager();
});