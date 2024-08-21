// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

import { observer } from 'mobx-react';
import * as React from 'react';
import { classWithModifiers, Modifiers } from 'utils/css';
import { trans } from 'utils/lang';
import MessageLengthCounter from './message-length-counter';

interface CommonProps {
  for?: string;
  labelKey?: string;
  maxLength?: number;
  modifiers?: Modifiers;
}

interface SimpleInput {
  hasError?: boolean;
  input?: string;
  showError?: boolean;
}

// extra props when error marking support is used.
type Props = CommonProps & SimpleInput;

// TODO: look at combining with ValidatingInput
// TODO: show error message
const InputContainer = observer((props: React.PropsWithChildren<Props>) => {
  const error = props.hasError && props.showError;

  return (
    <label className={classWithModifiers('input-container', { error }, props.modifiers)} htmlFor={props.for}>
      {props.labelKey != null && (
        <div className='input-container__label'>
          {trans(props.labelKey)}
          {props.maxLength != null && (
            <MessageLengthCounter
              maxLength={props.maxLength}
              message={props.input ?? ''}
            />
          )}
        </div>
      )}
      {props.children}
    </label>
  );
});

export default InputContainer;
