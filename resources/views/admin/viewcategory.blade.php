@extends('admin.maindesign')

@section('view_category')

@if(session('deletecategory_message'))
    <div style="margin-bottom: 10px; color: black; background-color: orangered;">
        {{ session('deletecategory_message') }}
    </div>
@endif

<table style="width: 100%; border-collapse: collapse; font-family: Arial, sans-serif;">
    <thead>
    <tr style="background-color: #f2f2f2;">
        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;">Category ID</th>
        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;">Category Name</th>
        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;">Action</th>
    </tr>
    </thead>
    <tbody>
    @foreach($categories as $category)
    <tr style="border-bottom: 1px solid #ddd;">
        <td style="padding: 12px;">{{ $category->id }}</td>
        <td style="padding: 12px;">{{ $category->category }}</td>
        <td style="padding: 12px;">
            <a href="{{ route('admin.categoryupdate', $category->id) }}" style="color: green;" onclick="return confirm('Do you want to update this?')">Update</a>
            <form action="{{ route('admin.categorydelete', $category->id) }}" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" style="background:none;border:none;padding:0;color:#0d6efd;" onclick="return confirm('Do you want to delete this?')">Delete</button>
            </form>
        </td>
    </tr>
    @endforeach
    </tbody>
</table>

@endsection
