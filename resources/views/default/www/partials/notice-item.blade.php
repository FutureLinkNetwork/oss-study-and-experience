<li>
    <a href="{{ $url }}">
        <span class="date">{{ $notice_date_display }}</span>
        @if(isset($target_labels) && is_array($target_labels))
            @foreach($target_labels as $target)
                <span class="{{ $target['class'] }}">{{ $target['label'] }}</span>
            @endforeach
        @endif
        <span class="li_text">{{ $title }}</span>
    </a>
</li>

