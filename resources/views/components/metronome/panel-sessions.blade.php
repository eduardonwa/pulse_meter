{{-- Independent Classic and Drill sessions are hidden from navigation.
     Their playback logic remains available for routine exercises. --}}
@can('use-pro')
<div class="modes" x-show="activeTab === 'sessions'" x-cloak>
    @include('components.metronome.sessions.creative')
</div>
@endcan
