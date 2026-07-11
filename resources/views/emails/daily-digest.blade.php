<x-mail::message>
# Today's trending stories

Here are the top stories from the platforms you follow.

@foreach ($posts as $post)
**{{ $post->title }}**
{{ ucfirst($post->source) }} · score {{ $post->trending_score }}

<x-mail::button :url="$post->url">
Read
</x-mail::button>

---
@endforeach

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
