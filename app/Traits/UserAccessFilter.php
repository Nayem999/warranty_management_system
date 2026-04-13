<?php

namespace App\Traits;

use App\Models\Brand;
use App\Models\ServiceCenter;

trait UserAccessFilter
{
    protected function applyBrandFilter($query, $user)
    {
        if (! $user->is_admin && $user->brandAccess()->exists()) {
            $brandIds = $user->brandAccess()->pluck('brand_id')->toArray();
            $query->whereIn('brand_id', $brandIds);
        }
    }

    protected function applyServiceCenterFilter($query, $user, string $foreignKey = 'service_center_id')
    {
        if (! $user->is_admin && $user->serviceCenterAccess()->exists()) {
            $serviceCenterIds = $user->serviceCenterAccess()->pluck('service_center_id')->toArray();
            $query->whereIn($foreignKey, $serviceCenterIds);
        }
    }

    protected function getAccessibleBrandIds($user): array
    {
        if ($user->is_admin) {
            return Brand::pluck('id')->toArray();
        }

        return $user->brandAccess()->pluck('brand_id')->toArray() ?: [];
    }

    protected function getAccessibleServiceCenterIds($user): array
    {
        if ($user->is_admin) {
            return ServiceCenter::pluck('id')->toArray();
        }

        return $user->serviceCenterAccess()->pluck('service_center_id')->toArray() ?: [];
    }
}
