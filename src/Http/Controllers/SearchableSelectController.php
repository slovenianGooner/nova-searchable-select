<?php

namespace Sloveniangooner\SearchableSelect\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\ResourceIndexRequest;

class SearchableSelectController extends Controller
{
    public function index(ResourceIndexRequest $request)
    {
        $searchable = $request->get("searchable", false);
        $resource = $request->resource();
        $label = $request->get("label", $resource::$title);
        $labelPrefix = $request->get("labelPrefix", false);

        if ($searchable && $request->filled('search')) {
            $items = $request->model()::search($request->get('search'));
        } else {
            $items = $request->toQuery();
        }

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
    
        if (!$searchable) { // Don't apply the relatableQuery if not searchable, it won't handle it
            $request->resource()::relatableQuery($request, $items);
        }

        $items = $items->get()->makeVisible(['display', 'value'])->each(function ($item) use ($request, $labelPrefix, $label) {
            $item->display = '';
            if($labelPrefix) {
                $item->display .= $item->{$labelPrefix} . ': ';
            }
            $item->display .= $item->{$label};
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
