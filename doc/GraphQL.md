# GraphQL

## Fetch Song list with keyword

```
query {
  readSongs(keyword: "hisa") {
    ID
    Title
    Artist
    Album
    Length
    StreamFile {
      url
    }
  }
}
```

## Fetch Playlist list

```
query {
  readPlaylists {
    ID
    Title
    Description
    Songs {
      ID
      Title
      Length
      Artist
      Album
      Disc
      Track
    }
  }
}
```

## Create playlist

```
mutation CreatePlaylist($input: PlaylistCreateInputType!) {
  createPlaylist(Input: $input) {
    ID
  }
}

{
  "input": {
    "Title": "title"
  }
}
```