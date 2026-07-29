@php
    $scheduleLine1 = static function (array $event): string {
        $recurrence = trim((string) ($event['recurrence_label'] ?? ''));
        if ($recurrence !== '') {
            return $recurrence;
        }
        $date = trim((string) ($event['event_date'] ?? ''));
        if ($date === '') {
            return '';
        }
        $ts = strtotime($date);

        return $ts ? date('l, j F Y', $ts) : $date;
    };
    $scheduleLine2 = static function (array $event): string {
        $display = trim((string) ($event['schedule_display'] ?? ''));
        if ($display !== '') {
            return $display;
        }
        $time = (string) ($event['event_time'] ?? '');
        if ($time === '') {
            return '';
        }
        $ts = strtotime('1970-01-01 '.$time);

        return $ts ? date('g:i A', $ts) : substr($time, 0, 5);
    };
@endphp
@if (count($events) === 0)
    <div class="text-center text-muted py-5">
        <p>Upcoming events will appear here soon. Please check back.</p>
    </div>
@else
    <div class="ag-events-carousel wow fadeIn" id="agEventsCarousel" data-wow-delay="0.15s">
        <div class="ag-events-track">
            @foreach ($events as $index => $event)
                @php
                    $icon = $iconClass((string) ($event['icon_class'] ?? 'fa-church'));
                    $line1 = $scheduleLine1($event);
                    $line2 = $scheduleLine2($event);
                    $cardClass = 'ag-event-card ag-events-slide'.($index === 0 ? ' active' : '').($index % 2 === 1 ? ' ag-event-card--reverse' : '');
                @endphp
                <article class="{{ $cardClass }}">
                    <div class="ag-event-card__media">
                        <img src="{{ $event['image_url'] ?? asset('site/img/events-1.jpg') }}" class="ag-event-card__img" alt="{{ $event['title'] ?? 'Church event' }}">
                        <span class="ag-event-card__category">{{ $event['public_category_label'] ?? 'Event' }}</span>
                    </div>
                    <div class="ag-event-card__body">
                        <div class="ag-event-card__schedule">
                            <span class="ag-event-card__icon"><i class="fa {{ $icon }}"></i></span>
                            <div>
                                @if ($line1 !== '')
                                    <span class="ag-event-card__recurrence">{{ $line1 }}</span>
                                @endif
                                @if ($line2 !== '')
                                    <span class="ag-event-card__time">{{ $line2 }}</span>
                                @endif
                            </div>
                        </div>
                        <h3 class="ag-event-card__title">{{ $event['title'] ?? '' }}</h3>
                        <p class="ag-event-card__text">{{ $event['description'] ?? '' }}</p>
                        @if (trim((string) ($event['cta_url'] ?? '')) !== '')
                            <a href="{{ $event['cta_url'] }}" class="ag-event-card__cta">{{ $event['cta_text'] ?? 'Learn more' }} <i class="fa fa-arrow-right ms-2"></i></a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
        @if (count($events) > 1)
            <div class="ag-events-controls">
                <button type="button" class="ag-events-nav ag-events-nav--prev" aria-label="Previous event">
                    <i class="fa fa-chevron-left"></i>
                </button>
                <div class="ag-events-dots" aria-label="Event slides">
                    @foreach ($events as $index => $event)
                        <button type="button" class="{{ $index === 0 ? 'active' : '' }}" data-slide="{{ $index }}" aria-label="{{ $event['title'] ?? 'Event' }}"></button>
                    @endforeach
                </div>
                <button type="button" class="ag-events-nav ag-events-nav--next" aria-label="Next event">
                    <i class="fa fa-chevron-right"></i>
                </button>
            </div>
        @endif
    </div>
@endif
