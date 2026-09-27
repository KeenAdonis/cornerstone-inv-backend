<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized
     * to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply
     * to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'role' => [
                'required',
                Rule::in([
                    'admin',
                    'branch_coordinator',
                    'warehouse_coordinator',
                ]),
            ],

            /*
             * Legacy single-assignment fields.
             *
             * These remain temporarily for backward
             * compatibility with the existing frontend.
             */
            'branch_id' => [
                'nullable',
                'integer',
                'exists:branches,id',
            ],

            'warehouse_id' => [
                'nullable',
                'integer',
                'exists:warehouses,id',
            ],

            /*
             * Multiple branch assignments.
             */
            'branch_ids' => [
                'nullable',
                'array',
            ],

            'branch_ids.*' => [
                'integer',
                'exists:branches,id',
                'distinct',
            ],

            /*
             * Multiple warehouse assignments.
             */
            'warehouse_ids' => [
                'nullable',
                'array',
            ],

            'warehouse_ids.*' => [
                'integer',
                'exists:warehouses,id',
                'distinct',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ];
    }

    /**
     * Configure additional validation rules
     * for role-based assignments.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $role = $this->input('role');

            $branchIds = $this->input(
                'branch_ids',
                []
            );

            $warehouseIds = $this->input(
                'warehouse_ids',
                []
            );

            /*
             * Maintain compatibility with the
             * existing single-assignment fields.
             */
            if (
                empty($branchIds) &&
                $this->filled('branch_id')
            ) {
                $branchIds = [
                    $this->input('branch_id'),
                ];
            }

            if (
                empty($warehouseIds) &&
                $this->filled('warehouse_id')
            ) {
                $warehouseIds = [
                    $this->input('warehouse_id'),
                ];
            }

            $hasBranchAssignment =
                !empty($branchIds);

            $hasWarehouseAssignment =
                !empty($warehouseIds);

            if (
                $role === 'admin' &&
                (
                    $hasBranchAssignment ||
                    $hasWarehouseAssignment
                )
            ) {
                $validator->errors()->add(
                    'role',
                    'Admin users cannot be assigned to a branch or warehouse.'
                );
            }

            if (
                $role === 'branch_coordinator' &&
                !$hasBranchAssignment
            ) {
                $validator->errors()->add(
                    'branch_ids',
                    'At least one branch assignment is required for a Branch Coordinator.'
                );
            }

            if (
                $role === 'branch_coordinator' &&
                $hasWarehouseAssignment
            ) {
                $validator->errors()->add(
                    'warehouse_ids',
                    'A Branch Coordinator cannot be assigned to a warehouse.'
                );
            }

            if (
                $role === 'warehouse_coordinator' &&
                !$hasWarehouseAssignment
            ) {
                $validator->errors()->add(
                    'warehouse_ids',
                    'At least one warehouse assignment is required for a Warehouse Coordinator.'
                );
            }

            if (
                $role === 'warehouse_coordinator' &&
                $hasBranchAssignment
            ) {
                $validator->errors()->add(
                    'branch_ids',
                    'A Warehouse Coordinator cannot be assigned to a branch.'
                );
            }
        });
    }
}