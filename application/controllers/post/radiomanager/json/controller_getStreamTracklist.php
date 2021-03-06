<?php

$stream_id  = application::post("stream_id", NULL, REQ_INT);
$from       = application::post("from", 0, REQ_INT);
$filter     = application::post("filter", 0, REQ_STRING);

$stream     = application::singular('stream', $stream_id);

if(!$stream->exists())
{
    misc::errorJSON("STREAM_NOT_EXISTS");
}

if($stream->getOwner() != user::getCurrentUserId())
{
    misc::errorJSON("NO_ACCESS");
}

$tracks = $stream->getTracks($from, config::getSetting("json", "tracks_per_query"));

echo json_encode($tracks);

