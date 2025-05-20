## Hướng dẫn cài đặt

1. **Clone source về**

    ```bash
    git clone https://gitlab.com/regn97-group/datn-su25.git
    cd datn-su25
    ```

2. **Cài đặt thư viện PHP**

    ```bash
    composer update
    ```

3. **Cài đặt JS & build assets**

    ```bash
    npm install
    npm run build
    ```

4. **Thiết lập biến môi trường**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

5. **Chạy migration & seeder**

    ```bash
    php artisan migrate
    php artisan db:seed
    ```

6. **Khởi động server**

    ```bash
    composer run dev
    ```
