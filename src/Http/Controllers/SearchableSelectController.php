<?php

namespace Sloveniangooner\SearchableSelect\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\ResourceIndexRequest;

class SearchableSelectController extends Controller
{
    public function index(ResourceIndexRequest $request)
    {
        $items = $request->toQuery();
        if ($request->has("use_resource_ids")) {
            $ids = $request->has("resource_ids") ? json_decode($request->get("resource_ids")) : [];
            $items = $items->whereIn($request->get("value"), $ids);
        }

        if ($request->has("ignore_resource_ids")) {
            $ids = $request->has("resource_ids") ? json_decode($request->get("resource_ids")) : [];
            $items = $items->whereNotIn($request->get("value"), $ids);
        }


        if ($request->has("max")) {
            $items = $items->take($request->get("max"));
        }

        $items = $items->get([$request->get("label"), $request->get("value")])->each(function ($item) use ($request) {
            $item->display = $item->{$request->get("label")};
            $item->value = $item->{$request->get("value")};
        });

        $resource = $request->resource();

        return response()->json([
            "label" => $resource::label(),
            "resources" => $items,
        ]);
    }

    /**
     * Get the paginator instance for the index request.
     *
     * @param  \Laravel\Nova\Http\Requests\ResourceIndexRequest  $request
     * @param  string  $resource
     * @return \Illuminate\Pagination\Paginator
     */
    protected function paginator(ResourceIndexRequest $request, $resource)
    {
        return $request->toQuery()->simplePaginate(
            $request->viaRelationship()
                        ? $resource::$perPageViaRelationship
                        : ($request->perPage ?? 25)
        );
    }
}
