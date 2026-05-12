import './bootstrap';

// 搜索功能
document.addEventListener('DOMContentLoaded', () => {
    // 搜索建议
    const searchInputs = document.querySelectorAll('.a-search input');
    searchInputs.forEach(input => {
        input.addEventListener('input', debounce((e) => {
            const query = e.target.value.trim();
            if (query.length >= 2) {
                // 这里可以添加搜索建议的AJAX请求
                console.log('搜索:', query);
            }
        }, 300));
    });

    // 模态框功能
    initModals();

    // 表单验证
    initFormValidation();

    // 点赞和收藏
    initLikeAndFavorite();

    // 评论功能
    initComments();

    // 图片懒加载
    initLazyLoad();

    // 返回顶部
    initBackToTop();

    // 无限滚动
    initInfiniteScroll();
});

// 防抖函数
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// 模态框初始化
function initModals() {
    // 打开模态框
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const modalId = btn.getAttribute('data-modal-open');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    });

    // 关闭模态框
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // 点击背景关闭
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
}

// 表单验证
function initFormValidation() {
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', (e) => {
            let isValid = true;
            const inputs = form.querySelectorAll('input[required], textarea[required]');

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    showError(input, '此字段不能为空');
                } else {
                    clearError(input);
                }
            });

            if (!isValid) {
                e.preventDefault();
            }
        });
    });
}

function showError(input, message) {
    clearError(input);
    const error = document.createElement('span');
    error.className = 'error-message';
    error.textContent = message;
    input.parentNode.appendChild(error);
    input.classList.add('error');
}

function clearError(input) {
    const error = input.parentNode.querySelector('.error-message');
    if (error) {
        error.remove();
    }
    input.classList.remove('error');
}

// 点赞和收藏功能
function initLikeAndFavorite() {
    document.querySelectorAll('[data-action="like"], [data-action="favorite"]').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const action = btn.getAttribute('data-action');
            const contentId = btn.getAttribute('data-content-id');

            try {
                const response = await fetch(`/api/v1/contents/${contentId}/${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                if (response.ok) {
                    btn.classList.toggle('active');
                    const countSpan = btn.querySelector('.count');
                    if (countSpan) {
                        const currentCount = parseInt(countSpan.textContent) || 0;
                        countSpan.textContent = btn.classList.contains('active') ? currentCount + 1 : currentCount - 1;
                    }
                }
            } catch (error) {
                console.error('操作失败:', error);
            }
        });
    });
}

// 评论功能
function initComments() {
    // 评论提交
    document.querySelectorAll('.a-comment-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const textarea = form.querySelector('textarea');
            const content = textarea.value.trim();

            if (!content) {
                alert('请输入评论内容');
                return;
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = '发布中...';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({ content })
                });

                if (response.ok) {
                    textarea.value = '';
                    alert('评论发布成功');
                    location.reload();
                } else {
                    alert('评论发布失败');
                }
            } catch (error) {
                console.error('评论发布失败:', error);
                alert('评论发布失败');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = '发表评论';
            }
        });
    });

    // 评论回复
    document.querySelectorAll('.a-comment-reply').forEach(btn => {
        btn.addEventListener('click', () => {
            // 这里可以添加回复功能
            console.log('回复评论');
        });
    });
}

// 图片懒加载
function initLazyLoad() {
    const images = document.querySelectorAll('img[data-src]');

    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    } else {
        // 降级方案
        images.forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    }
}

// 返回顶部
function initBackToTop() {
    const backToTopBtn = document.createElement('button');
    backToTopBtn.className = 'back-to-top';
    backToTopBtn.innerHTML = '↑';
    backToTopBtn.style.display = 'none';
    document.body.appendChild(backToTopBtn);

    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            backToTopBtn.style.display = 'block';
        } else {
            backToTopBtn.style.display = 'none';
        }
    });

    backToTopBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

// 无限滚动
function initInfiniteScroll() {
    const loadMoreBtn = document.querySelector('.a-load-more');
    if (!loadMoreBtn) return;

    let page = 1;
    let loading = false;

    loadMoreBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        if (loading) return;

        loading = true;
        loadMoreBtn.textContent = '加载中...';

        try {
            page++;
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);

            const response = await fetch(url);
            if (response.ok) {
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newItems = doc.querySelectorAll('.a-item-card, .a-feed-list > *');

                const container = document.querySelector('.a-feed-list, .a-card-grid');
                if (container && newItems.length > 0) {
                    newItems.forEach(item => container.appendChild(item));
                    loadMoreBtn.textContent = '加载更多';
                } else {
                    loadMoreBtn.textContent = '没有更多内容了';
                    loadMoreBtn.disabled = true;
                }
            }
        } catch (error) {
            console.error('加载失败:', error);
            loadMoreBtn.textContent = '加载失败，点击重试';
        } finally {
            loading = false;
        }
    });
}

