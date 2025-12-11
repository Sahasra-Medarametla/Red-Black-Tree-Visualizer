<?php
// CRITICAL: Must be the very first line to ensure session works correctly
session_start();

// Define constants for colors
define("RED", 0);
define("BLACK", 1);

// Node Class
class Node {
    public $key;
    public $color;
    public $left;
    public $right;
    public $parent;

    public function __construct($key, $color = RED, $parent = null) {
        $this->key = $key;
        $this->color = $color;
    }
}

// Red-Black Tree Class
class RedBlackTree {
    private $root;
    private $nil; // Sentinel node
    private $rotations = 0;
    private $node_count = 0;

    public function __construct() {
        $this->nil = new Node(null, BLACK);
        $this->nil->left = $this->nil;
        $this->nil->right = $this->nil;
        $this->root = $this->nil;
    }

    // --- Core RBT Operations: Rotations ---

    private function left_rotate($x) {
        $this->rotations++;
        $y = $x->right;
        $x->right = $y->left;
        if ($y->left != $this->nil) {
            $y->left->parent = $x;
        }
        $y->parent = $x->parent;
        if ($x->parent == $this->nil) {
            $this->root = $y;
        } elseif ($x == $x->parent->left) {
            $x->parent->left = $y;
        } else {
            $x->parent->right = $y;
        }
        $y->left = $x;
        $x->parent = $y;
    }

    private function right_rotate($y) {
        $this->rotations++;
        $x = $y->left;
        $y->left = $x->right;
        if ($x->right != $this->nil) {
            $x->right->parent = $y;
        }
        $x->parent = $y->parent;
        if ($y->parent == $this->nil) {
            $this->root = $x;
        } elseif ($y == $y->parent->right) {
            $y->parent->right = $x;
        } else {
            $y->parent->left = $x;
        }
        $x->right = $y;
        $y->parent = $x;
    }
    
    // RBT Insertion Fixup - Correctly implements rules 5, 6, 7 (Recolor and Rotate)
    private function insert_fixup($z) {
        while ($z->parent->color == RED) {
            if ($z->parent->parent == $this->nil) break; 

            // Check if parent is left child of grandparent
            if ($z->parent == $z->parent->parent->left) {
                $y = $z->parent->parent->right; // Uncle
                
                // Case 1/Rule 7: Uncle is Red (Recoloring required)
                if ($y->color == RED) { 
                    $z->parent->color = BLACK;
                    $y->color = BLACK;
                    $z->parent->parent->color = RED;
                    $z = $z->parent->parent; // Move z up to grandparent (Repeat loop)
                } else { // Uncle is Black (Rotation required - Rule 6)
                    
                    // Case 2: Zig-Zag (Node is right child) - Requires double rotation
                    if ($z == $z->parent->right) {
                        $z = $z->parent;
                        $this->left_rotate($z);
                    }
                    // Case 3/Rule 6: Zig-Zig (Node is left child) - Final rotation
                    $z->parent->color = BLACK;
                    $z->parent->parent->color = RED;
                    $this->right_rotate($z->parent->parent);
                }
            } else { 
                // Parent is right child of grandparent (Symmetric Cases)
                $y = $z->parent->parent->left; // Uncle

                // Case 4/Rule 7: Uncle is Red (Recoloring required)
                if ($y->color == RED) {
                    $z->parent->color = BLACK;
                    $y->color = BLACK;
                    $z->parent->parent->color = RED;
                    $z = $z->parent->parent; // Move z up to grandparent (Repeat loop)
                } else { // Uncle is Black (Rotation required - Rule 6)
                    
                    // Case 5: Zig-Zag (Node is left child)
                    if ($z == $z->parent->left) {
                        $z = $z->parent;
                        $this->right_rotate($z);
                    }
                    // Case 6/Rule 6: Zig-Zig (Node is right child) - Final rotation
                    $z->parent->color = BLACK;
                    $z->parent->parent->color = RED;
                    $this->left_rotate($z->parent->parent);
                }
            }
        }
        // Rule 2/3: Root node must be colored BLACK
        $this->root->color = BLACK;
    }

    private function rb_delete_fixup($x) {
        // Deletion fixup is highly complex and omitted for project simplicity.
    }

    // --- Deletion Helpers (for simplified BST deletion) ---

    private function find_node($key) {
        $current = $this->root;
        while ($current != $this->nil && $key != $current->key) {
            if ($key < $current->key) {
                $current = $current->left;
            } else {
                $current = $current->right;
            }
        }
        return ($current == $this->nil) ? null : $current;
    }

    private function rb_transplant($u, $v) {
        if ($u->parent == $this->nil) {
            $this->root = $v;
        } elseif ($u == $u->parent->left) {
            $u->parent->left = $v;
        } else {
            $u->parent->right = $v;
        }
        $v->parent = $u->parent;
    }
    
    private function minimum($node) {
        while ($node->left != $this->nil) {
            $node = $node->left;
        }
        return $node;
    }

    // --- INSERTION LOGIC ---
    public function insert($key) {
        if (!is_numeric($key)) return;
        $key = (int)$key;
        
        // Rule 5: Every new node must be inserted with RED colour.
        $z = new Node($key, RED, $this->nil);
        $z->left = $this->nil;
        $z->right = $this->nil;
        $y = $this->nil;
        $x = $this->root;

        // 1. Standard BST Insert 
        while ($x != $this->nil) {
            $y = $x;
            if ($z->key < $x->key) {
                $x = $x->left;
            } else {
                $x = $x->right; 
            }
        }
        
        $z->parent = $y;
        
        // Rule 1/2: Check whether the tree is empty / Insert as root (Black)
        if ($y == $this->nil) {
            $this->root = $z;
            $z->color = BLACK; 
        } elseif ($z->key < $y->key) {
            // Rule 3: Insert new node as a leaf
            $y->left = $z;
        } else {
            // Rule 3: Insert new node as a leaf
            $y->right = $z;
        }
        
        $this->node_count++;

        // Rule 4: If the parent of new node is RED, run fixup.
        if ($z->parent->color == RED) {
             $this->insert_fixup($z); 
        }
    }

    // --- RANDOM INSERTION, DELETION, STATS ---
    
    public function insert_random() {
        $rand_key = rand(1, 100);
        $this->insert($rand_key);
    }

    public function rb_delete($key) {
        // Simplified BST deletion only
        $z = $this->find_node((int)$key);
        if ($z === null) {
            return;
        }
        
        $y = $z;
        $y_original_color = $y->color;
        $x = $this->nil; 

        if ($z->left == $this->nil) {
            $x = $z->right;
            $this->rb_transplant($z, $z->right);
        } elseif ($z->right == $this->nil) {
            $x = $z->left;
            $this->rb_transplant($z, $z->left);
        } else {
            $y = $this->minimum($z->right);
            $y_original_color = $y->color;
            $x = $y->right;

            if ($y->parent == $z) {
                $x->parent = $y;
            } else {
                $this->rb_transplant($y, $y->right);
                $y->right = $z->right;
                $y->right->parent = $y;
            }

            $this->rb_transplant($z, $y);
            $y->left = $z->left;
            $y->left->parent = $y;
            $y->color = $z->color;
        }
        
        $this->node_count--;
    }

    public function to_array($node = null) {
        // Converts the tree structure to a JSON-serializable array for D3.js
        if ($node === null) {
            $node = $this->root;
        }
        if ($node == $this->nil) { 
            return null;
        }
        
        $array = [
            'key' => $node->key,
            'color' => ($node->color == RED) ? 'red' : 'black',
            'children' => []
        ];
        
        $left_child = $this->to_array($node->left);
        if ($left_child !== null) {
            $array['children'][] = $left_child;
        }
        
        $right_child = $this->to_array($node->right);
        if ($right_child !== null) {
            $array['children'][] = $right_child;
        }
        
        return $array;
    }

    private function get_height_recursive($node) {
        if ($node == $this->nil) {
            return 0;
        }
        return 1 + max($this->get_height_recursive($node->left), $this->get_height_recursive($node->right));
    }
    
    public function get_stats() {
        return [
            'nodes' => $this->node_count,
            'rotations' => $this->rotations,
            'height' => $this->get_height_recursive($this->root)
        ];
    }
    
    public function reset_tree() {
        $this->root = $this->nil;
        $this->rotations = 0;
        $this->node_count = 0;
    }
}

// --- Server Command Handling ---
header('Content-Type: application/json');

$rbt = null;

// Initialize tree or load from session
if (!isset($_SESSION['rbt_tree'])) {
    $rbt = new RedBlackTree();
    $_SESSION['rbt_tree'] = serialize($rbt);
} else {
    $rbt = unserialize($_SESSION['rbt_tree']);
}

$command = $_POST['command'] ?? 'get';
$value = $_POST['value'] ?? null;

if ($command == 'insert' && $value !== null) {
    $rbt->insert($value);
} elseif ($command == 'delete' && $value !== null) {
    $rbt->rb_delete($value);
} elseif ($command == 'random') {
    $rbt->insert_random();
} elseif ($command == 'reset') {
    $rbt->reset_tree();
} 

// Save the updated tree state back to the session
$_SESSION['rbt_tree'] = serialize($rbt);

// Output the full state to the client
echo json_encode([
    'tree' => $rbt->to_array(),
    'stats' => $rbt->get_stats()
]);
?>