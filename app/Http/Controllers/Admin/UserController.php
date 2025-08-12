<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Models\UserRole;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller {
    public function index()
{
    $users = User::with('role')->whereNull('deleted_at')->get();
    $userRoles = UserRole::select('id', 'name')->get(); // lấy tất cả vai trò

    return inertia('admin/users/Index', [
        'users' => $users,
        'userRoles' => $userRoles,
    ]);
}
 /**
     * Hiển thị chi tiết người dùng và danh sách ca làm việc của họ.
     */
    public function show(User $user)
    {
        // Chỉ lấy các UserShift liên quan đến người dùng này, sắp xếp theo ngày
        $userShifts = $user->userShifts()
            ->orderBy('date', 'desc')
            ->get();

        return Inertia::render('admin/users/Show', [
            'user' => $user->load('role'), // Tải vai trò của người dùng
            'userShifts' => $userShifts,
        ]);
    }
public function create()
    {   
        return Inertia::render('admin/users/Create', [
            'userRoles' => UserRole::select('id', 'name')->get(),
        ]);
    }

    // Xử lý thêm mới
    public function store(Request $request)
    {
        $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'phone_number' => 'required|string|unique:users,phone_number',
        'password' => 'required|string|min:6',
        'role_id' => 'required|exists:roles,id',
    ], [
        'email.unique' => 'Email đã tồn tại.',
        'phone_number.unique' => 'Số điện thoại đã tồn tại.',
    ]);
        
        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
            'phone_number' => $validated['phone_number'] ?? null,
            'role_id' => $validated['role_id'],
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Thêm người dùng thành công');
    }
// UserController.php

public function edit($id)
{
    $user = User::findOrFail($id);
    $userRoles = UserRole::select('id', 'name')->get();

    return Inertia::render('admin/users/Edit', [
        'user' => $user,
        'userRoles' => $userRoles,
    ]);
}

public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $user->id,
        'phone_number' => 'required|string|unique:users,phone_number,' . $user->id,
        'role_id' => 'required|exists:roles,id',
    ], [
        'email.unique' => 'Email đã tồn tại.',
        'phone_number.unique' => 'Số điện thoại đã tồn tại.',
    ]);

    $user->update([
        ...$validated,
        'password' => $validated['password'] ? Hash::make($validated['password']) : $user->password,
    ]);

    return redirect()->route('admin.users.index')->with('success', 'Cập nhật người dùng thành công');
}
public function destroy(User $user)
{
    $user->delete(); // Laravel sẽ cập nhật cột deleted_at thay vì xoá thật
    return redirect()->back()->with('success', 'Xoá người dùng thành công');
}

}