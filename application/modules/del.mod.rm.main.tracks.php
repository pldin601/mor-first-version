<div class="rm_tracks_data" content="<?= base64_encode(json_encode(track::getTracks(user::getCurrentUserId(), 0, config::getSetting("json", "tracks_per_query")))) ?>"></div>
<div class="rm_tracks_wrap">
    <div class="rm_tracks_table">
        <div class="rm_tracks_head">
            <div class="rm_tracks_cell"></div>
            <div class="rm_tracks_cell">#</div>
            <div class="rm_tracks_cell">Title</div>
            <div class="rm_tracks_cell">Artist</div>
            <div class="rm_tracks_cell">Album</div>
            <div class="rm_tracks_cell">Genre</div>
            <div class="rm_tracks_cell">Duration</div>
            <div class="rm_tracks_cell">Track #</div>
        </div>
        <div class="rm_tracks_body rm_library"></div>
    </div>
</div>
