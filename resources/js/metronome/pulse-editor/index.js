import { grouping } from './grouping'
import { pattern } from './pattern'
import { sources } from './sources'
import { draft } from './draft'
import { persistence } from './persistence'
import { interaction } from './interaction'
import { pulseState } from './state'

export function pulseEditor() {
    return {
        ...pulseState(),
        ...grouping(),
        ...pattern(),
        ...sources(),
        ...draft(),
        ...persistence(),
        ...interaction(),
    }
}