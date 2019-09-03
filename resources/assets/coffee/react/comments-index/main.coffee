###
#    Copyright (c) ppy Pty Ltd <contact@ppy.sh>.
#
#    This file is part of osu!web. osu!web is distributed with the hope of
#    attracting more community contributions to the core ecosystem of osu!.
#
#    osu!web is free software: you can redistribute it and/or modify
#    it under the terms of the Affero GNU General Public License version 3
#    as published by the Free Software Foundation.
#
#    osu!web is distributed WITHOUT ANY WARRANTY; without even the implied
#    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
#    See the GNU Affero General Public License for more details.
#
#    You should have received a copy of the GNU Affero General Public License
#    along with osu!web.  If not, see <http://www.gnu.org/licenses/>.
###

import { Comment } from 'comment'
import { Observer } from 'mobx-react'
import core from 'osu-core-singleton'
import * as React from 'react'
import { button, div, h1, p, span } from 'react-dom-factories'

el = React.createElement

export class Main extends React.Component
  constructor: (props) ->
    super props

    @pagination = React.createRef()


  componentDidMount: =>
    pagination = document.querySelector('.js-comments-pagination').cloneNode(true)
    @pagination.current.innerHTML = ''
    @pagination.current.appendChild pagination


  render: =>
    div null,
      div className: 'header-v3 header-v3--comments',
        div className: 'header-v3__bg'
        div className: 'header-v3__overlay'
        div className: 'osu-page osu-page--header-v3',
          @renderHeaderTitle()
          @renderHeaderTabs()

      el Observer, null, () =>
        div className: 'osu-page osu-page--comments',
          for comment in @props.comments
            el Comment,
              key: comment.id
              comment: comment
              userVotesByCommentId: @props.userVotesByCommentId
              showReplies: false
              showCommentableMeta: true
              linkParent: true
              depth: 0
              modifiers: ['dark']

          div ref: @pagination


  renderHeaderTabs: =>
    div className: 'page-mode-v2 page-mode-v2--comments',
      span
        className: 'page-mode-v2__link page-mode-v2__link--active'
        osu.trans 'comments.index.title.info'


  renderHeaderTitle: =>
    div className: 'osu-page-header-v3 osu-page-header-v3--comments',
      div className: 'osu-page-header-v3__title',
        div className: 'osu-page-header-v3__title-icon',
          div className: 'osu-page-header-v3__icon'
        h1
          className: 'osu-page-header-v3__title-text'
          dangerouslySetInnerHTML:
            __html: osu.trans 'comments.index.title._',
              info: "<span class='osu-page-header-v3__title-highlight'>#{osu.trans('comments.index.title.info')}</span>"
