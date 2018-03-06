<?php

/**
 *    Copyright 2015-2017 ppy Pty. Ltd.
 *
 *    This file is part of osu!web. osu!web is distributed with the hope of
 *    attracting more community contributions to the core ecosystem of osu!.
 *
 *    osu!web is free software: you can redistribute it and/or modify
 *    it under the terms of the Affero GNU General Public License version 3
 *    as published by the Free Software Foundation.
 *
 *    osu!web is distributed WITHOUT ANY WARRANTY; without even the implied
 *    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *    See the GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with osu!web.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Http\Controllers;

use App;
use App\Libraries\CurrentStats;
use App\Libraries\PostSearch;
use App\Libraries\Search;
use App\Models\BeatmapDownload;
use App\Models\Beatmapset;
use App\Models\Forum\Post;
use App\Models\News;
use App\Models\User;
use Auth;
use Request;
use View;

class HomeController extends Controller
{
    protected $section = 'home';

    public function __construct()
    {
        $this->middleware('auth', [
            'only' => [
                'downloadQuotaCheck',
                'search',
                'quickSearch',
            ],
        ]);

        return parent::__construct();
    }

    public function bbcodePreview()
    {
        $post = new Post(['post_text' => Request::input('text')]);

        return $post->bodyHTML;
    }

    public function downloadQuotaCheck()
    {
        return [
            'quota_used' => BeatmapDownload::where('user_id', Auth::user()->user_id)->count(),
        ];
    }

    public function getDownload()
    {
        return view('home.download');
    }

    public function getIcons()
    {
        return view('home.icons')
        ->with('icons', [
            'osu',
            'mode-osu',
            'mode-mania',
            'mode-fruits',
            'mode-taiko',
            'social-patreon',
        ]);
    }

    public function index()
    {
        $host = Request::getHttpHost();
        $subdomain = substr($host, 0, strpos($host, '.'));

        if ($subdomain === 'store') {
            return ujs_redirect(route('store.products.index'));
        }

        if (Auth::check()) {
            $news = News\Index::all();
            $newBeatmapsets = Beatmapset::latestRankedOrApproved();
            $popularBeatmapsetsPlaycount = Beatmapset::mostPlayedToday();
            $popularBeatmapsetIds = array_keys($popularBeatmapsetsPlaycount);
            $popularBeatmapsets = Beatmapset::whereIn('beatmapset_id', $popularBeatmapsetIds)
                ->orderByField('beatmapset_id', $popularBeatmapsetIds)
                ->get();

            return view('home.user', compact(
                'newBeatmapsets',
                'news',
                'popularBeatmapsets',
                'popularBeatmapsetsPlaycount'
            ));
        } else {
            return view('home.landing', ['stats' => new CurrentStats()]);
        }
    }

    public function osuSupportPopup()
    {
        return view('objects._popup_support_osu');
    }

    public function quickSearch()
    {
        $search = new Search([
            'query' => Request::input('query'),
            'limit' => 5,
        ]);

        if (!$search->hasQuery()) {
            return response([], 204);
        }

        return view('home.nav_search_result', compact('search'));
    }

    public function search()
    {
        $mode = request('mode');

        if ($mode === 'beatmapset') {
            return ujs_redirect(route('beatmapsets.index', ['q' => Request::input('query')]));
        } elseif ($mode === 'user_posts') {
            return $this->searchUserPosts();
        }

        $params = array_merge(Request::all(), [
            'user' => Auth::user(),
            'mode' => $mode,
        ]);

        $search = new Search($params);

        if ($search->mode === Search::DEFAULT_MODE) {
            $search->params['limit'] = 8;
        }

        return view('home.search', compact('search'));
    }

    public function searchUserPosts()
    {
        $user = User::lookup(request('username'));
        $options = [
            'query' => request('query'),
            'userId' => $user !== null ? $user->getKey() : -1,
            'forumId' => request('forum_id'),
            'includeSubforums' => get_bool(request('forum_children')),
        ];

        $search = (new PostSearch($options))
            ->paginate(50)
            ->appends(request()->query());

        return view('home.search_posts', compact('search'));
    }

    public function setLocale()
    {
        $newLocale = get_valid_locale(Request::input('locale'));
        App::setLocale($newLocale);

        if (Auth::check()) {
            Auth::user()->update([
                'user_lang' => $newLocale,
            ]);
        }

        return js_view('layout.ujs-reload')
            ->withCookie(cookie()->forever('locale', $newLocale));
    }

    public function supportTheGame()
    {
        return view('home.support-the-game')
        ->with('data', [
            // why support's blocks
            'blocks' => [
                // localization's name => icon
                'dev' => 'user',
                'time' => 'clock-o',
                'ads' => 'thumbs-up',
                'goodies' => 'star',
            ],

            // supporter's perks
            'perks' => [
                // localization's name => icon
                'osu_direct' => 'search',
                'auto_downloads' => 'cloud-download',
                'upload_more' => 'cloud-upload',
                'early_access' => 'flask',
                'customisation' => 'picture-o',
                'beatmap_filters' => 'filter',
                'yellow_fellow' => 'fire',
                'speedy_downloads' => 'dashboard',
                'change_username' => 'magic',
                'skinnables' => 'paint-brush',
                'feature_votes' => 'thumbs-up',
                'sort_options' => 'trophy',
                'feel_special' => 'heart',
                'more_to_come' => 'gift',
            ],
        ]);
    }
}
