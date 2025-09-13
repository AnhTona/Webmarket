document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('forgotForm');
    const emailInput = document.getElementById('emailInput');
    const errorMessage = document.getElementById('errorMessage');

    // Danh sách email giả lập từ cơ sở dữ liệu
    const registeredUsers = [
        'test@example.com',
        'user123@gmail.com',
        'admin@webmarket.com',
        '0912345678', // Số điện thoại giả lập
    ];

    form.addEventListener('submit', (event) => {
        event.preventDefault(); // Ngăn form gửi đi

        const userInput = emailInput.value.trim();

        // Kiểm tra xem email/số điện thoại có trong "cơ sở dữ liệu" không
        if (registeredUsers.includes(userInput)) {
            // Nếu hợp lệ, ẩn thông báo lỗi và xóa border đỏ
            errorMessage.style.display = 'none';
            emailInput.style.border = 'none';

            // Hiển thị modal thành công
            const messageBox = document.createElement('div');
            messageBox.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                padding: 20px;
                background-color: #fff;
                border: 1px solid #ccc;
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                z-index: 1000;
                border-radius: 8px;
                text-align: center;
            `;
            messageBox.innerHTML = `
                <p>Yêu cầu đặt lại mật khẩu đã được gửi đi!</p>
                <button onclick="this.parentNode.remove()" style="margin-top: 10px;">OK</button>
            `;
            document.body.appendChild(messageBox);
        } else {
            // Nếu không hợp lệ, hiển thị thông báo lỗi và thêm border đỏ
            errorMessage.style.display = 'block';
            emailInput.style.border = '2px solid #ff0000';
        }
    });
});
