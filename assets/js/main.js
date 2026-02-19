// =========================================
// MAIN JAVASCRIPT
// =========================================

// Initialize AOS on page load
document.addEventListener('DOMContentLoaded', function() {
  // AOS is initialized in footer.php
  
  // Navbar scroll effect
  const navbar = document.querySelector('.navbar');
  if (navbar) {
    window.addEventListener('scroll', () => {
      if (window.scrollY > 50) {
        navbar.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
      } else {
        navbar.style.boxShadow = '0 1px 3px rgba(0, 0, 0, 0.1)';
      }
    });
  }
});

// Utility function for API calls
async function apiCall(url, method = 'GET', data = null) {
  const options = {
    method: method,
    headers: {
      'Content-Type': 'application/json'
    }
  };
  
  if (data && method !== 'GET') {
    if (data instanceof FormData) {
      delete options.headers['Content-Type'];
      options.body = data;
    } else {
      options.body = JSON.stringify(data);
    }
  }
  
  try {
    const response = await fetch(url, options);
    return await response.json();
  } catch (error) {
    console.error('API call error:', error);
    throw error;
  }
}

// Format date
function formatDate(dateString) {
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(dateString).toLocaleDateString('en-US', options);
}

// Format time
function formatTime(dateString) {
  const options = { hour: 'numeric', minute: '2-digit', hour12: true };
  return new Date(dateString).toLocaleTimeString('en-US', options);
}

// Show toast notification
function showToast(message, type = 'info') {
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: ${type === 'success' ? '#51CF66' : type === 'error' ? '#EB5757' : '#2D9CDB'};
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 10001;
    animation: slideIn 0.3s ease-out;
  `;
  toast.textContent = message;
  
  document.body.appendChild(toast);
  
  setTimeout(() => {
    toast.style.animation = 'slideOut 0.3s ease-out';
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

// Add CSS for toast animations
const style = document.createElement('style');
style.textContent = `
  @keyframes slideIn {
    from {
      transform: translateX(100%);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  
  @keyframes slideOut {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(100%);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);