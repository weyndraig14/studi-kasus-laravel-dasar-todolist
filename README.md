# STUDI KASUS LARAVEL DASAR

</center>

<center>

## POINT UTAMA

</center>

### 1. Membuat project

-   Minimal versi `PHP` 8,

-   Minimal versi `Composer` 2,

-   Pada cmd gunakan perintah `composer create-project laravel/laravel=9.1.5 studi-kasus-laravel-dasar-todolist`.

---

### 2. Membuat logic login

-   Dalam studi kasus ini, saya tidak menggunakan database untuk menyimpan user login dan todo,

-   Dan untuk logic login nya diganti dengan menggunakan dengan menggunakan session `array` untuk user login.

    ```
     private array $users = [
        "agung" => "rahasia" // username & pass
    ];

    // function untuk login
    function login(string $user, string $password): bool
    {
        if(!isset($this->users[$user])){
            return false;
        }

        $correctPassword = $this->users[$user];
        return $password == $correctPassword;
    }
    ```

-   Unit test login

    ```
    public function testLoginSuccess() //ketika login berhasil
    {
        $this->post('/login', [
            "user" => "agung",
            "password" => "rahasia"
        ])->assertRedirect("/")
            ->assertSessionHas("user", "agung");
    }
    ```

---

### 3. Template halaman web

-   Template halaman web berada didalam directory `resources/view/`.

-   Test template

    ```
    Route::view('/template', 'template');
    ```

---

### 4. Membuat user controller

-   Untuk memanggil halaman login & logout.

-   Function User login benar

    ```
      public function login(): Response
    {
        return response()
            ->view("user.login", [
                "title" => "Login"
            ]);
    }
    ```

-   Function user login ketika salah

    ```
    public function doLogin(Request $request): Response|RedirectResponse
    {
        $user = $request->input('user');
        $password = $request->input('password');

        // validate input
        if (empty($user) || empty($password)) {
            return response()->view("user.login", [
                "title" => "Login",
                "error" => "User or password is required"
            ]);
        }

        if ($this->userService->login($user, $password)) {
            $request->session()->put("user", $user);
            return redirect("/");
        }

        return response()->view("user.login", [
            "title" => "Login",
            "error" => "User or password is wrong"
        ]);
    }
    ```

-   Fuction user logout

    ```
    public function doLogout(Request $request): RedirectResponse
    {
        $request->session()->forget("user");
        return redirect("/");
    }
    ```

-   Menampilkan halaman login & logout

    ```
    Route::controller(\App\Http\Controllers\UserController::class)->group(function () {
    Route::get('/login', 'login')->middleware([\App\Http\Middleware\OnlyGuestMiddleware::class]);
    Route::post('/login', 'doLogin')->middleware([\App\Http\Middleware\OnlyGuestMiddleware::class]);
    Route::post('/logout', 'doLogout')->middleware([\App\Http\Middleware\OnlyMemberMiddleware::class]);
    });
    ```

---

### 5. Membuat Todolist Service

-   Todolist service

    ```
    private TodolistService $todolistService;

    public function __construct(TodolistService $todolistService)
    {
        $this->todolistService = $todolistService;
    }
    ```

-   Unit test todolist service

    ```
    private TodolistService $todolistService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->todolistService = $this->app->make(TodolistService::class);
    }
    public function testTodolistNotNull()
    {
        self::assertNotNull($this->todolistService);
    }
    ```

---

### 6. Membuat logic Menambah Todo

-   Kode menambah todo

    ```
        public function saveTodo(string $id, string $todo): void; // app/Services
    ```

    ```
    public function saveTodo(string $id, string $todo): void
    {
        if (!Session::exists("todolist")) {
            Session::put("todolist", []);
        }

        Session::push("todolist", [
            "id" => $id,
            "todo" => $todo
        ]);
    } // app/Services/Impl
    ```

-   Unit test menambah todo

    ```
    public function testSaveTodo()
    {
        $this->todolistService->saveTodo("2", "Vior");

        $todolist = Session::get("todolist");
        foreach ($todolist as $value) {
            self::assertEquals("2", $value['id']);
            self::assertEquals("Vior", $value['todo']);
        }
    }
    ```

---

### 7. Membuat Logic Mengambil Todo

-   Kode mengambil todo

    ```
        public function getTodolist(): array;
    ```

    ```
     public function getTodolist(): array
    {
        return Session::get("todolist", []);
    }
    ```

-   Unit test mengambil todo

    ```
    // Ketika todo kosong
    public function testGetTodolistEmpty()
    {
        self::assertEquals([], $this->todolistService->getTodolist());
    }

    // Ketika ada todo
    public function testGetTodolistNotEmpty()
    {
        $expected = [
            [
                "id" => "1",
                "todo" => "Vior"
            ],
            [
                "id" => "2",
                "todo" => "Okto"
            ]
        ];

        $this->todolistService->saveTodo("1", "Vior");
        $this->todolistService->saveTodo("2", "Okto");

        self::assertEquals($expected, $this->todolistService->getTodolist());
    }
    ```

---

### 8. Membuat Logic Menghapus Todo

-   Kode menghapus todo

    ```
        public function removeTodo(string $todoId);
    ```

    ```
    ublic function removeTodo(string $todoId)
    {
        $todolist = Session::get("todolist");

        foreach ($todolist as $index => $value) {
            if ($value['id'] == $todoId) {
                unset($todolist[$index]);
                break;
            }
        }

        Session::put("todolist", $todolist);
    }
    ```

-   Unit test menghapus todo

    ```
        public function testRemoveTodo()
    {
        $this->todolistService->saveTodo("1", "Vior");
        $this->todolistService->saveTodo("2", "Okto"); // Tambah todo

        self::assertEquals(2, sizeof($this->todolistService->getTodolist()));

        $this->todolistService->removeTodo("3"); // hapus todo yang tidak ada

        self::assertEquals(2, sizeof($this->todolistService->getTodolist()));

        $this->todolistService->removeTodo("1"); // hapus todo yang ada

        self::assertEquals(1, sizeof($this->todolistService->getTodolist()));

        $this->todolistService->removeTodo("2");

        self::assertEquals(0, sizeof($this->todolistService->getTodolist()));
    }
    ```

---

### 9. Membuat Todolist Controller

-   Kode todo controller

    ```
    $todo = $request->input("todo");

        if (empty($todo)) {
            $todolist = $this->todolistService->getTodolist();
            return response()->view("todolist.todolist", [
                "title" => "Todolist",
                "todolist" => $todolist,
                "error" => "Todo is required"
            ]);
        }
    ```

---

### 10. Membuat Todolist Page

-   Kode membuat todolist page

    ```
    public function todoList(Request $request)
    {

    } // app/Controllers
    ```

    ```
    Route::controller(\App\Http\Controllers\TodolistController::class)->group(function () {
        Route::get('/todolist', 'todoList');
        Route::post('/todolist', 'addTodo');
        Route::post('/todolist/{id}/delete', 'removeTodo');
    });
    ```

---

### 11. Membuat Aksi Tambah & Hapus Todo

-   Kode aksi tambah todo

    ```
    public function addTodo(Request $request)
    {
        $todo = $request->input("todo");

        if (empty($todo)) {
            $todolist = $this->todolistService->getTodolist();
            return response()->view("todolist.todolist", [
                "title" => "Todolist",
                "todolist" => $todolist,
                "error" => "Todo is required"
            ]);
        }

        $this->todolistService->saveTodo(uniqid(), $todo);

        return redirect()->action([TodolistController::class, 'todoList']);
    } // app/Controllers

    ```

-   Kode aksi hapus todo

    ```
    public function removeTodo(Request $request, string $todoId): RedirectResponse
    {
        $this->todolistService->removeTodo($todoId);
        return redirect()->action([TodolistController::class, 'todoList']);
    }
    ```

---

### 12. Unit Test Aksi Tambah & Hapus Todo

-   Unit test tambah

    ```
    public function testAddTodoSuccess()
    {
        $this->withSession([
            "user" => "agung"
        ])->post("/todolist", [
            "todo" => "Vior"
        ])->assertRedirect("/todolist");
    }
    ```

-   Unit test aksi hapus

    ```
    public function testRemoveTodolist()
    {
        $this->withSession([
            "user" => "agung",
            "todolist" => [
                [
                    "id" => "1",
                    "todo" => "Vior"
                ],
                [
                    "id" => "2",
                    "todo" => "Okto"
                ]
            ]
        ])->post("/todolist/1/delete")
            ->assertRedirect("/todolist");
    }
    ```

---

### 13. Tampilan Login

-   Tampilan login
    <img width="944" alt="Cuplikan layar 2024-05-08 100418" src="https://github.com/weyndraig14/studi-kasus-laravel-dasar-todolist/assets/162102805/41199ea2-625b-4623-be69-0b480f611278">
    

-   Tampilan login ketika user/pass salah
    <img width="944" alt="Cuplikan layar 2024-05-08 100442" src="https://github.com/weyndraig14/studi-kasus-laravel-dasar-todolist/assets/162102805/f381ed09-39dc-4a88-9209-40a804430607">
    

-   Tampilan berhasil login
    <img width="938" alt="Cuplikan layar 2024-05-08 100513" src="https://github.com/weyndraig14/studi-kasus-laravel-dasar-todolist/assets/162102805/443d8d08-1fbd-4cba-b0be-41bb0850fe19">
    

-   Tambah todo
    <img width="928" alt="Cuplikan layar 2024-05-08 101321" src="https://github.com/weyndraig14/studi-kasus-laravel-dasar-todolist/assets/162102805/8cf421df-ee4c-47e5-ba89-cfd45a313869">
    

-   Tambah todo kosong  
    <img width="929" alt="Cuplikan layar 2024-05-08 101341" src="https://github.com/weyndraig14/studi-kasus-laravel-dasar-todolist/assets/162102805/f2e79c4d-6fb8-47b3-b7ee-2a6143e2ac38">


---

## PERTANYAAN & CATATAN TAMBAHAN

- 

## KESIMPULAN

- 
