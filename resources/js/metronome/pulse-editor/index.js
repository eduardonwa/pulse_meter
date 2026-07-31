import { grouping } from './grouping'
import { pattern } from './pattern'
import { sources } from './sources'
import { draft } from './draft'
import { persistence } from './persistence'
import { interaction } from './interaction'
import { pulseState } from './state'
import { collections } from './collections'

export function pulseEditor(pulsePresets = []) {
    return {
        ...pulseState(pulsePresets),
        ...grouping(),
        ...pattern(),
        ...sources(),
        ...collections(),
        ...draft(),
        ...persistence(),
        ...interaction(),
    }
}