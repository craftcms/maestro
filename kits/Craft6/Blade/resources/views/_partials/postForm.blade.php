@use('CraftCms\Cms\Support\Html')
<form method="post">
  {!! Html::csrfInput(['async' => true]) !!}
  {!! Html::actionInput('entries/save-entry') !!}
  {!! Html::successMessageInput('Entry posted') !!}
  {!! Html::failMessageInput('Something went wrong.') !!}
  {!! Html::redirectInput('/guestbook') !!}
  {!! Html::hiddenInput('sectionId', $id) !!}
  {!! Html::hiddenInput('title', 'Post ' . $now->format('Y-m-d H:i:s')) !!}

  <div class="mb-6 mt-4">
    <label for="post" class="font-bold">Message</label>
    <textarea name="fields[textBlock]" class="w-full px-6 py-4" required id="message"></textarea>
  </div>
  <input type="submit" class="rounded font-bold bg-red-600 text-slate-50 px-6 py-4" value="Post entry" />
</form>
