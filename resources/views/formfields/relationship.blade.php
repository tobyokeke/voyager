@if(isset($options->model) && isset($options->type))

    @if(class_exists($options->model))

        @php $relationshipField = $row->field; @endphp


        @if($options->type == 'belongsTo')

            @if(isset($view) && ($view == 'browse' || $view == 'read'))

                @php
                    $relationshipData = (isset($data)) ? $data : $dataTypeContent;
                    $model = app($options->model);
                    $dataType = \TCG\Voyager\Models\DataType::where('model_name',$options->model)->first();

                    $query = $model::where($options->key,$relationshipData->{$options->column})->first();
                @endphp

                @if(isset($query))
                    <p>{{ $query->{$options->label} }}</p>
                @else
                    <p>{{ __('voyager::generic.no_results') }}</p>
                @endif

            @else

                <select
                        class="form-control select2-ajax" name="{{ $options->column }}"
                        data-get-items-route="{{route('voyager.' . $dataType->slug.'.relation')}}"
                        data-get-items-field="{{$row->field}}"
                        @if(!is_null($dataTypeContent->getKey())) data-id="{{$dataTypeContent->getKey()}}" @endif
                        data-method="{{ !is_null($dataTypeContent->getKey()) ? 'edit' : 'add' }}"
                >
                    @php
                        $model = app($options->model);
                        $query = $model::where($options->key, old($options->column, $dataTypeContent->{$options->column}))->get();
                    @endphp

                    @if(!$row->required)
                        <option value="">{{__('voyager::generic.none')}}</option>
                    @endif

                    @foreach($query as $relationshipData)
                        <option value="{{ $relationshipData->{$options->key} }}" @if(old($options->column, $dataTypeContent->{$options->column}) == $relationshipData->{$options->key}) selected="selected" @endif>{{ $relationshipData->{$options->label} }}</option>
                    @endforeach
                </select>

            @endif

        @elseif($options->type == 'hasOne')

            @php
                $relationshipData = (isset($data)) ? $data : $dataTypeContent;

                $model = app($options->model);
                $query = $model::where($options->column, '=', $relationshipData->{$options->key})->first();

            @endphp

            @if(isset($query))
                <p>{{ $query->{$options->label} }}</p>
            @else
                <p>{{ __('voyager::generic.no_results') }}</p>
            @endif

        @elseif($options->type == 'hasMany')

            @if(isset($view) && ($view == 'browse' || $view == 'read'))

                @php
                    $relationshipData = (isset($data)) ? $data : $dataTypeContent;
                    $model = app($options->model);


                    $selected_values = $model::where($options->column, '=', $relationshipData->{$options->key})->paginate(request('per_page') ?? 30);

                    session()->flash('pagination',$selected_values->toArray());

                    $dataType = \TCG\Voyager\Models\DataType::where('model_name',get_class($model))->first();

                    $browse_rows = [];
                    foreach($dataType->rows as $row){
                        if($row->browse == 1) array_push($browse_rows,$row->field);
                    }

                    session(['browse_rows' => $browse_rows]);

                    $selected_values = $selected_values->map(function($item,$key) use($dataType,$options){$item = collect($item)
                    ->put('relationship_slug',$dataType->slug)
                    ->put('relationship_field',$options->label)
                    ->toJson();
                    return json_decode($item);
                    });


                @endphp

                @if($view == 'browse')
                    @php
                        $selected_values = collect($selected_values)->pluck($options->label);

                        $string_values = implode(", ", $selected_values->all());
                        if(mb_strlen($string_values) > 25){ $string_values = mb_substr($string_values, 0, 25) . '...'; }
                    @endphp
                    @if(empty($selected_values))
                        <p>{{ __('voyager::generic.no_results') }}</p>
                    @else
                        <p>{{ $string_values }}</p>
                    @endif
                @else
                    @if(empty($selected_values))
                        <p>{{ __('voyager::generic.no_results') }}</p>
                    @else
                        <table class="table table-striped">

                            @php


                                $browse_rows = session("browse_rows");
                                $keys = $browse_rows;

                                foreach($selected_values as $item){
                                    $keys = array_keys((array)$item);

                                    $keys = collect($keys)->intersect($browse_rows);
                                }


                            @endphp
                            <tr>
                                @if($options->serial_numbers ?? true)
                                    <th>S/N</th>
                                @endif
                                @foreach($keys as $key)
                                    <th>{{$key}}</th>
                                @endforeach
                                <th></th><!-- for actions -->
                            </tr>
                            @foreach($selected_values as $selected_value)

                                <tr>
                                    @if($options->serial_numbers ?? true)
                                        <td>{{session('pagination')['from'] + $loop->iteration}}</td>
                                    @endif
                                    @foreach($keys as $key)
                                        <td>{{$selected_value->$key}}</td>
                                    @endforeach

                                    <td>
                                        <a href="{{ '/'. config('voyager.admin_path'). '/' . $selected_value->relationship_slug . '/' .$selected_value->id}}">
                                            <btn class="btn btn-success">View</btn>
                                            {{--                                                @php--}}
                                            {{--                                                    $label = $selected_value->relationship_field;--}}
                                            {{--                                                    echo $selected_value->$label;--}}

                                            {{--                                                @endphp--}}
                                        </a>

                                    </td>

                                </tr>
                            @endforeach

                        </table>
                        <nav aria-label="Page navigation example">
                            <ul class="pagination">
                                <li class="page-item">
                                    <a class="page-link" href="{{session('pagination')['prev_page_url']}}" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                        <span class="sr-only">Previous</span>
                                    </a>
                                </li>
                                <li class="page-item"><a class="page-link" href="#">Current Page: {{session('pagination')['current_page']}}</a></li>
                                {{--                                <li class="page-item"><a class="page-link" href="#">2</a></li>--}}
                                {{--                                <li class="page-item"><a class="page-link" href="#">3</a></li>--}}
                                <li class="page-item">
                                    <a class="page-link" href="{{session('pagination')['next_page_url']}}" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                        <span class="sr-only">Next</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>

                    @endif
                @endif

            @else

                @php
                    $model = app($options->model);
                    $query = $model::where($options->column, '=', $dataTypeContent->{$options->key})->get();
                @endphp

                @if(isset($query))
                    <ul>
                        @foreach($query as $query_res)
                            <li>{{ $query_res->{$options->label} }}</li>
                        @endforeach
                    </ul>

                @else
                    <p>{{ __('voyager::generic.no_results') }}</p>
                @endif

            @endif

        @elseif($options->type == 'belongsToMany')

            @if(isset($view) && ($view == 'browse' || $view == 'read'))

                @php
                    $relationshipData = (isset($data)) ? $data : $dataTypeContent;

                    $selected_values = isset($relationshipData) ? $relationshipData->belongsToMany($options->model, $options->pivot_table, $options->foreign_pivot_key ?? null, $options->related_pivot_key ?? null, $options->parent_key ?? null, $options->key)->get()->map(function ($item, $key) use ($options) {
            			return $item->{$options->label};
            		})->all() : array();
                @endphp

                @if($view == 'browse')
                    @php
                        $string_values = implode(", ", $selected_values);
                        if(mb_strlen($string_values) > 25){ $string_values = mb_substr($string_values, 0, 25) . '...'; }
                    @endphp
                    @if(empty($selected_values))
                        <p>{{ __('voyager::generic.no_results') }}</p>
                    @else
                        <p>{{ $string_values }}</p>
                    @endif
                @else
                    @if(empty($selected_values))
                        <p>{{ __('voyager::generic.no_results') }}</p>
                    @else
                        <ul>
                            @foreach($selected_values as $selected_value)
                                <li>{{ $selected_value }}</li>
                            @endforeach
                        </ul>
                    @endif
                @endif

            @else
                <select
                        class="form-control @if(isset($options->taggable) && $options->taggable === 'on') select2-taggable @else select2-ajax @endif"
                        name="{{ $relationshipField }}[]" multiple
                        data-get-items-route="{{route('voyager.' . $dataType->slug.'.relation')}}"
                        data-get-items-field="{{$row->field}}"
                        @if(!is_null($dataTypeContent->getKey())) data-id="{{$dataTypeContent->getKey()}}" @endif
                        data-method="{{ !is_null($dataTypeContent->getKey()) ? 'edit' : 'add' }}"
                        @if(isset($options->taggable) && $options->taggable === 'on')
                        data-route="{{ route('voyager.'.\Illuminate\Support\Str::slug($options->table).'.store') }}"
                        data-label="{{$options->label}}"
                        data-error-message="{{__('voyager::bread.error_tagging')}}"
                        @endif
                >

                    @php
                        $selected_values = isset($dataTypeContent) ? $dataTypeContent->belongsToMany($options->model, $options->pivot_table, $options->foreign_pivot_key ?? null, $options->related_pivot_key ?? null, $options->parent_key ?? null, $options->key)->get()->map(function ($item, $key) use ($options) {
                            return $item->{$options->key};
                        })->all() : array();
                        $relationshipOptions = app($options->model)->all();
                    $selected_values = old($relationshipField, $selected_values);
                    @endphp

                    @if(!$row->required)
                        <option value="">{{__('voyager::generic.none')}}</option>
                    @endif

                    @foreach($relationshipOptions as $relationshipOption)
                        <option value="{{ $relationshipOption->{$options->key} }}" @if(in_array($relationshipOption->{$options->key}, $selected_values)) selected="selected" @endif>{{ $relationshipOption->{$options->label} }}</option>
                    @endforeach

                </select>

            @endif

        @endif

    @else

        cannot make relationship because {{ $options->model }} does not exist.

    @endif

@endif
