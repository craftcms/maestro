@use('CraftCms\Cms\Support\Html')
<form method="post" accept-charset="UTF-8">
  {!! Html::csrfInput(['async' => true]) !!}
  {!! Html::actionInput('users/login') !!}
  {!! Html::redirectInput('/guestbook') !!}
  {!! Html::successMessageInput('Signed in successfully') !!}
  {!! Html::failMessageInput('Error signing in') !!}

  <div class="mb-6 mt-4">
    <label for="loginName" class="font-bold">Username or email</label>
    {!! Html::input('text', 'loginName', '', [
      'id' => 'loginName',
      'class' => 'w-full px-6 py-4',
    ]) !!}
  </div>
  <div class="mb-6">
    <label for="password" class="font-bold">Password</label>
    {!! Html::input('password', 'password', '', [
      'id' => 'password',
      'class' => 'w-full px-6 py-4',
    ]) !!}
  </div>
  <input type="submit" class="rounded font-bold bg-red-600 text-slate-50 px-6 py-4" value="Sign in" />
</form>
