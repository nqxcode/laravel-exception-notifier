@extends('laravel-exception-notifier::layout')

@section('content')

    <p><span style="color: rgb(79, 15, 143);">Внимание!</span> На сайте <a href="{{{ request()->root() }}}">{{{ request()->root() }}}</a> произошло исключение.</p>
    <div style="font-size: 14px;">
        <div><strong>Описание возникшего исключения</strong>:</div>

        <div style="margin: 10px; font-size: 13px">
            @foreach([
                [
                    'name' => 'Код состояния HTTP',
                    'value' => $code,
                ],
                [
                    'name' => 'Тип интерфейса между веб-сервером и PHP',
                    'value' => $sapi,
                ],
                [
                    'name' => 'Класс исключения',
                    'value' => get_class($exception),
                ],
                [
                    'name' => 'Код исключения',
                    'value' => $exception->getCode(),
                ],
                [
                    'name' => 'Сообщение исключения',
                    'value' => $exception->getMessage(),
                    'valueColor' => 'red'
                ],
                [
                    'name' => 'Файл, в котором произошло исключение',
                    'value' => $exception->getFile(),
                ],
                [
                    'name' => 'Строка, в которой произошло исключение',
                    'value' => $exception->getLine(),
                ],
            ] as $rowIndex => $row)
                @if ($row['value'] !== '')
                    <div><strong style="color: rgb(142, 137, 162);">{{{ $row['name'] }}}</strong>: <span style="font-weight: 600; color: {{{ data_get($row, 'valueColor', 'black') }}}">{{{ $row['value'] }}}</span></div>
                @endif
            @endforeach
        </div><br>

        <div><strong>[Stack trace]</strong>:
            <br>
            <div style="font-size: 11px; color: rgb(107,104,127);">
                @foreach(explode(PHP_EOL, $exception->getTraceAsString()) as $traceLine)
                    {{{ $traceLine }}}<br>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@section('footer')
    <div style="color: gray; font-size: 12px; margin: 20px 0; padding-top: 10px; border-top: 1px dashed;">
        <div style="margin: 0 15px;">
            <i>Файл с дампом исключения см. во вложении.</i>
        </div>
    </div>
@stop

