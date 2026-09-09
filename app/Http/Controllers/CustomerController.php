<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class CustomerController extends Controller
{
    const UPLOAD_DIR = 'uploads/customers';

    public function index()
    {
        $customers = User::role('customer')->with('customerProfile')->latest()->get();
        return view('customers.index', compact('customers'));
    }

    public function show(User $customer)
    {
        $customer->load('customerProfile');
        return view('customers.view', compact('customer'));
    }

    public function edit(User $customer)
    {
        $customer->load('customerProfile');
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, User $customer)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email,' . $customer->id,
            'phone'                 => 'nullable|string|max:20',
            'status'                => 'required|in:active,inactive',
            'photo'                 => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'date_of_birth'         => 'nullable|date|before:today',
            'alternate_phone'       => 'nullable|string|max:20',
            'gender'                => 'nullable|in:male,female,other',
            'address_line1'         => 'nullable|string|max:255',
            'address_line2'         => 'nullable|string|max:255',
            'city'                  => 'nullable|string|max:100',
            'state'                 => 'nullable|string|max:100',
            'postal_code'           => 'nullable|string|max:20',
            'country'               => 'nullable|string|max:100',
            'newsletter_subscribed' => 'nullable|boolean',
        ]);

        $data = [
            'name'   => $request->name,
            'email'  => $request->email,
            'phone'  => $request->phone,
            'status' => $request->status,
        ];

        if ($request->boolean('remove_photo')) {
            $this->deletePhoto($customer->photo);
            $data['photo'] = null;
        } elseif ($request->hasFile('photo')) {
            $this->deletePhoto($customer->photo);
            $data['photo'] = $this->savePhoto($request->file('photo'));
        }

        $customer->update($data);

        $customer->customerProfile()->updateOrCreate(
            ['user_id' => $customer->id],
            [
                'date_of_birth'         => $request->date_of_birth ?: null,
                'alternate_phone'       => $request->alternate_phone,
                'gender'                => $request->gender,
                'address_line1'         => $request->address_line1,
                'address_line2'         => $request->address_line2,
                'city'                  => $request->city,
                'state'                 => $request->state,
                'postal_code'           => $request->postal_code,
                'country'               => $request->country,
                'newsletter_subscribed' => $request->boolean('newsletter_subscribed'),
            ]
        );

        return response()->json([
            'status'   => 'success',
            'message'  => 'Customer updated successfully! ✅',
            'redirect' => route('customers.index'),
        ]);
    }

    public function destroy(User $customer)
    {
        $this->deletePhoto($customer->photo);
        $customer->customerProfile()->delete();
        $customer->delete();

        return response()->json(['status' => 'success', 'message' => 'Customer deleted successfully!']);
    }

    private function savePhoto($file): string
    {
        $uploadPath = base_path(self::UPLOAD_DIR);
        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        $filename = 'cust_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($uploadPath, $filename);

        return self::UPLOAD_DIR . '/' . $filename;
    }

    private function deletePhoto(?string $relativePath): void
    {
        if ($relativePath && File::exists(base_path($relativePath))) {
            File::delete(base_path($relativePath));
        }
    }

    public function toggleStatus(User $customer)
{
    $customer->status = $customer->status === 'active' ? 'inactive' : 'active';
    $customer->save();

    return response()->json([
        'status'     => 'success',
        'message'    => 'Customer marked as ' . ucfirst($customer->status) . '!',
        'new_status' => $customer->status,
    ]);
}
}