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
    $currentUserId = auth()->id();
    $users = User::with('role')
        ->whereNull('deleted_at')
      
        ->get();
    $userRoles = UserRole::select('id', 'name')->get();

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
    $userShifts = $user->userShifts()
        ->orderBy('date', 'desc')
        ->get();

    // Trả về đầy đủ thông tin user
    return Inertia::render('admin/users/Show', [
        'user' => $user->load('role'),
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
        'name' => 'required|string|max:150',
        'email' => 'required|email|unique:users,email',
        'phone_number' => 'required|string|max:20|unique:users,phone_number',
        'role_id' => 'required|exists:user_roles,id',
        'address' => 'nullable|string|max:255',
        'is_active' => 'required|boolean',
    ], [
        'name.required' => 'Vui lòng nhập tên nhân viên.',
        'email.required' => 'Vui lòng nhập email.',
        'email.email' => 'Email không đúng định dạng.',
        'email.unique' => 'Email đã tồn tại.',
        'phone_number.required' => 'Vui lòng nhập số điện thoại.',
        'phone_number.unique' => 'Số điện thoại đã tồn tại.',
        'phone_number.max' => 'Số điện thoại không được vượt quá 20 ký tự.',
        'role_id.required' => 'Vui lòng chọn chức vụ.',
        'role_id.exists' => 'Chức vụ không hợp lệ.',
        'address.max' => 'Địa chỉ không được vượt quá 255 ký tự.',
        'is_active.required' => 'Vui lòng chọn trạng thái.',
        'is_active.boolean' => 'Trạng thái không hợp lệ.',
    ]);

    $validated['is_active'] = filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN);

    User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'phone_number' => $validated['phone_number'],
        'role_id' => $validated['role_id'],
        'address' => $validated['address'] ?? null,
        'is_active' => $validated['is_active'],
        'password' => \Hash::make('12345678'),
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
        'email' => [
            'required',
            'email',
            Rule::unique('users', 'email')->ignore($user->id),
        ],
        'phone_number' => [
            'required',
            'string',
            'max:10',
            Rule::unique('users', 'phone_number')->ignore($user->id),
        ],
        'address' => 'nullable|string|max:255',
        'is_active' => 'required|boolean',
        'role_id' => 'required|exists:user_roles,id',
    ], [
        'name.required' => 'Vui lòng nhập tên nhân viên.',
        'email.required' => 'Vui lòng nhập email.',
        'email.email' => 'Email không đúng định dạng.',
        'email.unique' => 'Email đã tồn tại.',
        'phone_number.required' => 'Vui lòng nhập số điện thoại.',
        'phone_number.unique' => 'Số điện thoại đã tồn tại.',
        'phone_number.max' => 'Số điện thoại không được vượt quá 10 ký tự.',
        'role_id.required' => 'Vui lòng chọn chức vụ.',
        'role_id.exists' => 'Chức vụ không hợp lệ.',
        'address.max' => 'Địa chỉ không được vượt quá 255 ký tự.',
        'is_active.required' => 'Vui lòng chọn trạng thái.',
        'is_active.boolean' => 'Trạng thái không hợp lệ.',
    ]);

    $validated['is_active'] = filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN);

    $user->update($validated);

    return redirect()->route('admin.users.index')->with('success', 'Cập nhật người dùng thành công');
}
public function destroy(User $user)
{
    // Không cho xóa nếu là quản trị viên
    if ($user->role && $user->role->name !== 'Nhân viên bán hàng') {
        return redirect()->back()->with('error', 'Bạn chỉ có thể xóa nhân viên bán hàng!');
    }

    // Không cho xóa tài khoản đang đăng nhập
    if ($user->id === auth()->id()) {
        return redirect()->back()->with('error', 'Bạn không thể xóa tài khoản đang đăng nhập!');
    }

    // Kiểm tra nhân viên bán hàng đang trong ca làm việc
    $hasActiveShift = $user->userShifts()
        ->whereDate('date', now()->toDateString())
        ->whereNull('end_time') // Giả sử ca làm việc chưa kết thúc nếu end_time là null
        ->exists();

    if ($hasActiveShift) {
        return redirect()->back()->with('error', 'Nhân viên đang trong ca làm việc, không thể xóa!');
    }

    $user->delete();
    return redirect()->back()->with('success', 'Xoá người dùng thành công');
}

}