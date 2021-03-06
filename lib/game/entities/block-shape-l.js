ig.module( 
	'game.entities.block-shape-l'
)
.requires(
	'game.entities.block',
    'game.entities.block-container'
)
.defines(function() {

    EntityBlockShapeL = EntityBlockContainer.extend({
    
        color: EntityBlock.color.COLOR_L,    
        // hilmor 'i'
        // rotationShapes: [
        //     [[1,0],
        //      [1,0]], 
        //     [[0,0],
        //      [1,1],
        //      [0,0]]
             
        // ]
        // longer 
         rotationShapes: [
            [[1,0],
             [1,0],
             [1,0],
             [1,0]], 
            [[0,0,0,0],
             [1,1,1,1],
             [0,0,0,0]]
             
        ]
    });
});