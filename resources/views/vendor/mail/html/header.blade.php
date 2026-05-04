@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
{{-- Override of Laravel's default mail header. Uses the musicExams.help
     logo asset (same S3 URL referenced by Dashboard.vue) so transactional
     emails are visually branded rather than rendering as plain text. --}}
<img src="https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/musicexamshelp_logo2.png"
     alt="musicExams.help"
     class="logo"
     style="display: inline-block; max-height: 60px; width: auto; max-width: 280px;">
</a>
</td>
</tr>
